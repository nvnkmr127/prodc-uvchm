@extends('layouts.theme')

@section('title', 'API Token Usage')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">API Token Usage Statistics</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Tokens
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h4>{{ $totalTokens ?? 0 }}</h4>
                                    <p class="mb-0">Total Tokens</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h4>{{ $activeTokens ?? 0 }}</h4>
                                    <p class="mb-0">Active Tokens</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h4>{{ $expiredTokens ?? 0 }}</h4>
                                    <p class="mb-0">Expired Tokens</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h4>{{ $totalRequests ?? 0 }}</h4>
                                    <p class="mb-0">Total Requests</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Token Name</th>
                                    <th>User</th>
                                    <th>Last Used</th>
                                    <th>Total Requests</th>
                                    <th>Created At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tokens ?? [] as $token)
                                    <tr>
                                        <td>{{ $token->name }}</td>
                                        <td>
                                            {{ $token->tokenable->name ?? 'N/A' }}
                                            <small class="text-muted d-block">{{ $token->tokenable->email ?? '' }}</small>
                                        </td>
                                        <td>
                                            @if($token->last_used_at)
                                                {{ $token->last_used_at->format('M d, Y H:i') }}
                                                <small class="text-muted d-block">{{ $token->last_used_at->diffForHumans() }}</small>
                                            @else
                                                <span class="text-muted">Never used</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $token->request_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            {{ $token->created_at->format('M d, Y H:i') }}
                                            <small class="text-muted d-block">{{ $token->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if($token->expires_at && $token->expires_at->isPast())
                                                <span class="badge badge-danger">Expired</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No API tokens found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(isset($tokens) && method_exists($tokens, 'links'))
                        <div class="d-flex justify-content-center">
                            {{ $tokens->links() }}
                        </div>
                    @endif

                    <!-- Recent Activity -->
                    @if(isset($recentActivity) && count($recentActivity) > 0)
                        <div class="mt-4">
                            <h5>Recent API Activity</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Token</th>
                                            <th>Endpoint</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentActivity as $activity)
                                            <tr>
                                                <td>{{ $activity['timestamp'] ?? 'N/A' }}</td>
                                                <td>{{ $activity['token_name'] ?? 'Unknown' }}</td>
                                                <td><code>{{ $activity['endpoint'] ?? 'N/A' }}</code></td>
                                                <td>
                                                    <span class="badge badge-{{ $activity['status'] == 200 ? 'success' : 'danger' }}">
                                                        {{ $activity['status'] ?? 'Unknown' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
    // Auto-refresh every 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000);
</script>
@endpush