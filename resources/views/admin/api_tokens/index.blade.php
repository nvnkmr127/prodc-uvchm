@extends('layouts.theme')
@section('title', 'API Token Management')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-key text-primary"></i> API Token Management
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">API Tokens</li>
            </ol>
        </nav>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- New Token Display -->
    @if(session('token'))
        <div class="alert alert-warning alert-dismissible fade show border-left-warning" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-2">
                        <strong>🔑 New API Token Generated!</strong>
                    </h6>
                    <p class="mb-2">
                        Token Name: <strong>{{ session('token_name') }}</strong><br>
                        <small class="text-muted">Please copy this token now. You will not be able to see it again for security reasons.</small>
                    </p>
                    <div class="input-group">
                        <input type="text" id="newToken" class="form-control font-monospace" 
                               value="{{ session('token') }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToken()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Generate New Token Card -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card shadow border-left-primary h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-plus-circle me-2"></i>Generate New API Token
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.api-tokens.store') }}" method="POST" id="tokenForm">
                        @csrf
                        <div class="mb-3">
                            <label for="user_id" class="form-label">
                                <i class="fas fa-user text-primary"></i> Select User
                            </label>
                            <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                <option value="">-- Choose a User --</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                    <small class="text-muted">- {{ $user->getRoleNames()->implode(', ') }}</small>
                                </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="token_name" class="form-label">
                                <i class="fas fa-tag text-primary"></i> Token Name
                            </label>
                            <input type="text" name="token_name" id="token_name" 
                                   class="form-control @error('token_name') is-invalid @enderror" 
                                   placeholder="e.g., Biometric Device, Mobile App, Third-party Integration"
                                   value="{{ old('token_name') }}" required>
                            @error('token_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Give your token a descriptive name to identify its purpose.</small>
                        </div>

                        <div class="mb-3">
                            <label for="abilities" class="form-label">
                                <i class="fas fa-shield-alt text-primary"></i> Token Abilities (Optional)
                            </label>
                            <select name="abilities[]" id="abilities" class="form-control" multiple>
                                <option value="*" selected>All Permissions</option>
                                <option value="read">Read Access Only</option>
                                <option value="write">Write Access</option>
                                <option value="attendance">Attendance Management</option>
                                <option value="students">Student Management</option>
                                <option value="reports">Generate Reports</option>
                            </select>
                            <small class="form-text text-muted">Leave empty or select "All Permissions" for full access.</small>
                        </div>

                        <div class="mb-3">
                            <label for="expires_at" class="form-label">
                                <i class="fas fa-clock text-primary"></i> Expiration Date (Optional)
                            </label>
                            <input type="datetime-local" name="expires_at" id="expires_at" class="form-control">
                            <small class="form-text text-muted">Leave empty for no expiration.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-key me-2"></i>Generate Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Token Statistics Card -->
        <div class="col-xl-7 col-lg-6 mb-4">
            <div class="card shadow border-left-info h-100">
                <div class="card-header bg-info text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-bar me-2"></i>Token Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-primary">
                                <div class="card-body py-3">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tokens</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $tokens->count() }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-success">
                                <div class="card-body py-3">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $tokens->filter(function($token) { 
                                            return !$token->expires_at || $token->expires_at->isFuture(); 
                                        })->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-warning">
                                <div class="card-body py-3">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Recently Used</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $tokens->filter(function($token) { 
                                            return $token->last_used_at && $token->last_used_at->isAfter(now()->subDays(7)); 
                                        })->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-danger">
                                <div class="card-body py-3">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Never Used</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $tokens->filter(function($token) { 
                                            return !$token->last_used_at; 
                                        })->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h6 class="font-weight-bold text-gray-800 mb-3">Quick Actions</h6>
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.api-tokens.usage') }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-chart-line"></i> Usage Report
                            </a>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="cleanupExpired()">
                                <i class="fas fa-broom"></i> Cleanup Expired
                            </button>
                            <a href="/api/documentation" target="_blank" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-book"></i> API Docs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Tokens Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-2"></i>Active API Tokens
            </h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <a class="dropdown-item" href="#" onclick="refreshTable()">
                        <i class="fas fa-sync-alt fa-sm fa-fw mr-2 text-gray-400"></i> Refresh
                    </a>
                    <a class="dropdown-item" href="#" onclick="exportTokens()">
                        <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i> Export
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($tokens->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tokensTable">
                        <thead class="thead-light">
                            <tr>
                                <th><i class="fas fa-user"></i> User</th>
                                <th><i class="fas fa-tag"></i> Token Name</th>
                                <th><i class="fas fa-shield-alt"></i> Abilities</th>
                                <th><i class="fas fa-clock"></i> Last Used</th>
                                <th><i class="fas fa-calendar"></i> Created</th>
                                <th><i class="fas fa-hourglass-end"></i> Expires</th>
                                <th><i class="fas fa-traffic-light"></i> Status</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tokens as $token)
                                @php
                                    $isExpired = $token->expires_at && $token->expires_at->isPast();
                                    $isActive = !$isExpired;
                                    $neverUsed = !$token->last_used_at;
                                @endphp
                                <tr class="{{ $isExpired ? 'table-danger' : ($neverUsed ? 'table-warning' : '') }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <img class="avatar-img rounded-circle" 
                                                     src="https://ui-avatars.com/api/?name={{ urlencode($token->tokenable->name) }}&background=007bff&color=fff" 
                                                     alt="{{ $token->tokenable->name }}">
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $token->tokenable->name }}</div>
                                                <small class="text-muted">{{ $token->tokenable->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-weight-bold">{{ $token->name }}</span>
                                        <br><small class="text-muted">ID: {{ $token->id }}</small>
                                    </td>
                                    <td>
                                        @if(empty($token->abilities) || in_array('*', $token->abilities))
                                            <span class="badge badge-primary">All Permissions</span>
                                        @else
                                            @foreach($token->abilities as $ability)
                                                <span class="badge badge-secondary">{{ ucfirst($ability) }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if($token->last_used_at)
                                            <span class="text-success">{{ $token->last_used_at->diffForHumans() }}</span>
                                            <br><small class="text-muted">{{ $token->last_used_at->format('M d, Y H:i') }}</small>
                                        @else
                                            <span class="text-warning">Never used</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $token->created_at->format('M d, Y') }}
                                        <br><small class="text-muted">{{ $token->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($token->expires_at)
                                            @if($isExpired)
                                                <span class="text-danger">Expired</span>
                                                <br><small class="text-muted">{{ $token->expires_at->format('M d, Y') }}</small>
                                            @else
                                                <span class="text-info">{{ $token->expires_at->format('M d, Y') }}</span>
                                                <br><small class="text-muted">{{ $token->expires_at->diffForHumans() }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Never expires</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isExpired)
                                            <span class="badge badge-danger">Expired</span>
                                        @elseif($neverUsed)
                                            <span class="badge badge-warning">Unused</span>
                                        @else
                                            <span class="badge badge-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.api-tokens.test', $token) }}" 
                                               class="btn btn-outline-info" title="Test Token" target="_blank">
                                                <i class="fas fa-vial"></i>
                                            </a>
                                            @if(!$isExpired)
                                                <button type="button" class="btn btn-outline-warning" 
                                                        onclick="regenerateToken({{ $token->id }})" title="Regenerate">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="revokeToken({{ $token->id }}, '{{ $token->name }}')" title="Revoke">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-key fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No API Tokens Found</h5>
                    <p class="text-muted">Generate your first API token using the form above to get started.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Token Revoke Modal -->
<div class="modal fade" id="revokeTokenModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Revoke API Token
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to revoke the token "<strong id="tokenNameToRevoke"></strong>"?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. Any applications using this token will immediately lose access.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="revokeTokenForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Revoke Token
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .avatar {
        width: 2.5rem;
        height: 2.5rem;
    }
    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0,123,255,.075);
    }
    .font-monospace {
        font-family: 'Courier New', monospace;
    }
</style>
@endpush

@push('scripts')
<script>
    function copyToken() {
        const tokenInput = document.getElementById('newToken');
        tokenInput.select();
        tokenInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        // Show feedback
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    }

    function revokeToken(tokenId, tokenName) {
        document.getElementById('tokenNameToRevoke').textContent = tokenName;
        document.getElementById('revokeTokenForm').action = `/admin/api-tokens/${tokenId}`;
        $('#revokeTokenModal').modal('show');
    }

    function regenerateToken(tokenId) {
        if (confirm('Are you sure you want to regenerate this token? The old token will be invalidated.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/api-tokens/${tokenId}/regenerate`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function cleanupExpired() {
        if (confirm('Are you sure you want to clean up all expired tokens?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.api-tokens.cleanup") }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function refreshTable() {
        location.reload();
    }

    function exportTokens() {
        // Implement export functionality
        alert('Export functionality coming soon!');
    }

    // Initialize tooltips
    $(document).ready(function() {
        $('[title]').tooltip();
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
@endpush