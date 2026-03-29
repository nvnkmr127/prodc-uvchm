@extends('layouts.theme')
@section('title', 'Inbound Lead Webhooks')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inbound Webhooks</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Inbound Lead Webhooks</h1>
        </div>
        <a href="{{ route('admin.inbound-webhooks.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Create New Webhook
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Active Lead Sources</h6>
            
            <!-- Bulk Actions -->
            <div id="bulkActions" style="display: none;">
                <form id="bulkForm" action="{{ route('admin.inbound-webhooks.bulk-action') }}" method="POST" class="d-inline-flex align-items-center">
                    @csrf
                    <input type="hidden" name="webhook_ids" id="webhook_ids">
                    <select name="action" class="form-control form-control-sm mr-2" style="width: 150px;">
                        <option value="">Bulk Actions...</option>
                        <option value="activate">Activate Selected</option>
                        <option value="deactivate">Deactivate Selected</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Apply this action to selected items?');">Apply</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="4%">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            <th>Name</th>
                            <th>Webhook URL</th>
                            <th>Source Label</th>
                            <th>Last Called</th>
                            <th>Stats (S/F)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($webhooks as $webhook)
                            <tr>
                                <td>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input webhook-checkbox" id="webhook_{{ $webhook->id }}" name="ids[]" value="{{ $webhook->id }}">
                                        <label class="custom-control-label" for="webhook_{{ $webhook->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $webhook->name }}</strong><br>
                                    <small class="text-muted">{{ $webhook->slug }}</small>
                                </td>
                                <td>
                                    <code>{{ $webhook->url }}</code>
                                    <button class="btn btn-sm p-0 ml-1" onclick="copyToClipboard('{{ $webhook->url }}')" title="Copy URL">
                                        <i class="fas fa-copy text-muted"></i>
                                    </button>
                                </td>
                                <td>{{ $webhook->source_name }}</td>
                                <td>{{ $webhook->last_called_at ? $webhook->last_called_at->diffForHumans() : 'Never' }}</td>
                                <td>
                                    <span class="text-success">{{ $webhook->success_count }}</span> / 
                                    <span class="text-danger">{{ $webhook->failure_count }}</span>
                                </td>
                                <td>
                                    @if($webhook->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.inbound-webhooks.show', $webhook) }}" class="btn btn-info btn-sm" title="Configure Mapping">
                                            <i class="fas fa-project-diagram"></i> Map Fields
                                        </a>
                                        <form action="{{ route('admin.inbound-webhooks.toggle', $webhook) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.inbound-webhooks.destroy', $webhook) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this webhook?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No inbound webhooks configured. Create one to start receiving leads.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $webhooks->links() }}
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('URL copied to clipboard');
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.webhook-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const webhookIdsInput = document.getElementById('webhook_ids');

    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.webhook-checkbox:checked').length;
        bulkActions.style.display = checkedCount > 0 ? 'block' : 'none';
        
        const selectedIds = Array.from(checkboxes)
            .filter(i => i.checked)
            .map(i => i.value);
        webhookIdsInput.value = selectedIds.join(',');
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(c => c.checked = selectAll.checked);
        updateBulkActions();
    });

    checkboxes.forEach(c => {
        c.addEventListener('change', updateBulkActions);
    });
});
</script>
@endsection
