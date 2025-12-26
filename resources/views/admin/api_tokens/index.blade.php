@extends('layouts.theme')
@section('title', 'API Keys Management')

@section('content')
    <div class="developer-hub">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800 font-weight-bold">API Keys Management</h1>
                <p class="text-muted mb-0">Create and manage access keys for your integrations.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/api/documentation" class="btn btn-outline-primary shadow-sm">
                    <i class="fas fa-book me-2"></i>Documentation
                </a>
                <a href="{{ route('admin.api-tokens.export') }}" class="btn btn-primary shadow-sm">
                    <i class="fas fa-download me-2"></i>Export CSV
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-left-success" role="alert">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-success text-white me-3"><i class="fas fa-check"></i></div>
                    <div>
                        <strong>Success!</strong> {{ session('success') }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- New Token Disclosure -->
        @if(session('token'))
            <div class="card border-warning border-left-warning shadow mb-4">
                <div class="card-body bg-warning bg-opacity-10">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-key fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="text-gray-900 font-weight-bold">Secret Key Generated</h5>
                            <p class="mb-3">This is the only time we will show you this key. Please store it securely.</p>

                            <div class="input-group mb-2">
                                <span
                                    class="input-group-text bg-white font-weight-bold text-primary">{{ session('token_name') }}</span>
                                <input type="text" id="newToken" class="form-control font-monospace bg-white text-dark"
                                    value="{{ session('token') }}" readonly>
                                <button class="btn btn-dark" type="button" onclick="copyToken()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <!-- Generator Card -->
            <div class="col-xl-4 mb-4">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-plus-circle me-2"></i>Generate New Key
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.api-tokens.store') }}" method="POST" id="tokenForm">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label small font-weight-bold text-uppercase text-muted">Token
                                    Owner</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">Select User...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small font-weight-bold text-uppercase text-muted">Friendly
                                    Name</label>
                                <input type="text" name="token_name" class="form-control" placeholder="e.g. Mobile App v2"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label
                                    class="form-label small font-weight-bold text-uppercase text-muted">Capabilities</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="*" name="abilities[]"
                                        id="allPerms" checked>
                                    <label class="form-check-label" for="allPerms">Full Access</label>
                                </div>
                                <!-- Add more granular permissions here if needed -->
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary py-2 font-weight-bold">
                                    Generate Key
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List Card -->
            <div class="col-xl-8 mb-4">
                <div class="card shadow border-0">
                    <div
                        class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-gray-800">Active Keys</h6>
                        <span class="badge bg-light text-primary border">{{ $tokens->count() }} Total</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 text-uppercase small font-weight-bold text-muted ps-4">Key Name</th>
                                    <th class="border-0 text-uppercase small font-weight-bold text-muted">User</th>
                                    <th class="border-0 text-uppercase small font-weight-bold text-muted">Last Used</th>
                                    <th class="border-0 text-uppercase small font-weight-bold text-muted">Status</th>
                                    <th class="border-0 text-uppercase small font-weight-bold text-muted text-end pe-4">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tokens as $token)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ $token->name }}</div>
                                            <small class="text-muted font-monospace">ID: {{ $token->id }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2 small">
                                                    {{ substr($token->tokenable->name ?? 'U', 0, 1) }}
                                                </div>
                                                <div>{{ $token->tokenable->name ?? 'Unknown' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($token->last_used_at)
                                                <span class="text-dark"
                                                    title="{{ $token->last_used_at }}">{{ $token->last_used_at->diffForHumans() }}</span>
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($token->expires_at && $token->expires_at->isPast())
                                                <span class="badge bg-danger-subtle text-danger">Expired</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">Active</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                    <li><a class="dropdown-item text-danger" href="#"
                                                            onclick="revokeToken({{ $token->id }}, '{{ $token->name }}')"><i
                                                                class="fas fa-trash me-2"></i>Revoke</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-key fa-3x mb-3 text-gray-300"></i><br>
                                            No active API keys found.
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

    <!-- Revoke Moadl -->
    <form id="revokeForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('styles')
    <style>
        .avatar-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            /* Changed from inline-flex to flex */
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }

        .bg-success-subtle {
            background-color: #d1e7dd;
        }

        .bg-danger-subtle {
            background-color: #f8d7da;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function copyToken() {
            const copyText = document.getElementById("newToken");
            copyText.select();
            document.execCommand("copy");
            // Optional: Tooltip or toast here
        }

        function revokeToken(id, name) {
            if (confirm(`Are you sure you want to revoke the key "${name}"? This cannot be undone.`)) {
                const form = document.getElementById('revokeForm');
                form.action = `/admin/api-tokens/${id}`;
                form.submit();
            }
        }
    </script>
@endpush