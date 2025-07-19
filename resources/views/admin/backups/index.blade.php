@extends('layouts.theme')
@section('title', 'Backup Management')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Backup Management</h1>
        <p class="mb-0 text-gray-600">Manage system backups and configuration</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="showCreateBackupModal()">
            <i class="fas fa-plus mr-1"></i>Create Backup
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <!-- Backup Statistics -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Backups</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ count($spatieBackups) + count($settingsBackups) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-database fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Auto Backup</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $backupConfig['auto_backup'] ? 'Enabled' : 'Disabled' }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-robot fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Disk Usage</div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $backupConfig['disk_usage']['percentage'] }}%</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $backupConfig['disk_usage']['percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hdd fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Last Backup</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $backupConfig['last_backup'] ? $backupConfig['last_backup']['date'] : 'Never' }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Backup Configuration -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-cog mr-2"></i>Backup Configuration
                </h6>
            </div>
            <div class="card-body">
                <form action="/admin/backups/settings" method="POST" id="backupSettingsForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="auto_backup" name="auto_backup" 
                                   {{ $backupConfig['auto_backup'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="auto_backup">Enable Auto Backup</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="backup_frequency">Backup Frequency</label>
                        <select class="form-control" id="backup_frequency" name="backup_frequency">
                            <option value="daily" {{ $backupConfig['backup_frequency'] === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $backupConfig['backup_frequency'] === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $backupConfig['backup_frequency'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="backup_retention_days">Retention Period (Days)</label>
                        <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days" 
                               value="{{ $backupConfig['backup_retention_days'] }}" min="1" max="365">
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="auto_cleanup" name="auto_cleanup" 
                                   {{ $backupConfig['auto_cleanup'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="auto_cleanup">Auto Cleanup Old Backups</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="backup_notifications" name="backup_notifications" 
                                   {{ $backupConfig['backup_notifications'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="backup_notifications">Email Notifications</label>
                        </div>
                    </div>

                    <div class="form-group" id="notification_email_group" style="{{ $backupConfig['backup_notifications'] ? '' : 'display: none;' }}">
                        <label for="notification_email">Notification Email</label>
                        <input type="email" class="form-control" id="notification_email" name="notification_email" 
                               value="{{ $backupConfig['notification_email'] }}">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i>Save Settings
                    </button>
                </form>

                <hr>

                <!-- Quick Actions -->
                <div class="mb-3">
                    <h6 class="text-gray-800 mb-2">Quick Actions</h6>
                    <button type="button" class="btn btn-outline-success btn-sm btn-block mb-2" onclick="testBackup()">
                        <i class="fas fa-vial mr-1"></i>Test Backup System
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm btn-block" onclick="cleanupBackups()">
                        <i class="fas fa-broom mr-1"></i>Cleanup Old Backups
                    </button>
                </div>

                <!-- Restore Settings -->
                <div>
                    <h6 class="text-gray-800 mb-2">Restore Settings</h6>
                    <form action="/admin/backups/restore-settings" method="POST" enctype="multipart/form-data" id="restoreForm">
                        @csrf
                        <div class="form-group">
                            <input type="file" class="form-control-file" name="backup_file" accept=".json" required>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm btn-block">
                            <i class="fas fa-upload mr-1"></i>Restore Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Lists -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list mr-2"></i>Available Backups
                </h6>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshBackups()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="backupTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="application-tab" data-toggle="tab" href="#application" role="tab">
                            <i class="fas fa-server mr-1"></i>Application Backups
                            <span class="badge badge-secondary ml-1">{{ count($spatieBackups) }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab">
                            <i class="fas fa-cog mr-1"></i>Settings Backups
                            <span class="badge badge-secondary ml-1">{{ count($settingsBackups) }}</span>
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="backupTabContent">
                    <!-- Application Backups -->
                    <div class="tab-pane fade show active" id="application" role="tabpanel">
                        @if(count($spatieBackups) > 0)
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered" id="applicationBackupsTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Filename</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Date</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($spatieBackups as $backup)
                                            <tr>
                                                <td>
                                                    <i class="fas fa-file-archive text-primary mr-2"></i>
                                                    <strong>{{ $backup['name'] }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $backup['type'] === 'Database' ? 'info' : ($backup['type'] === 'Files' ? 'secondary' : 'success') }}">
                                                        {{ $backup['type'] }}
                                                    </span>
                                                </td>
                                                <td>{{ $backup['size'] }}</td>
                                                <td>{{ $backup['date'] }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="/admin/backups/download/{{ $backup['name'] }}" 
                                                           class="btn btn-success" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button class="btn btn-danger" 
                                                                onclick="deleteBackup('{{ $backup['name'] }}')" 
                                                                title="Delete">
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
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
                                <h6 class="text-gray-600">No Application Backups Found</h6>
                                <p class="text-gray-500">Create your first application backup.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Settings Backups -->
                    <div class="tab-pane fade" id="settings" role="tabpanel">
                        @if(count($settingsBackups) > 0)
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered" id="settingsBackupsTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Filename</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Date</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settingsBackups as $backup)
                                            <tr>
                                                <td>
                                                    <i class="fas fa-file-code text-warning mr-2"></i>
                                                    <strong>{{ $backup['name'] }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-warning">{{ $backup['type'] }}</span>
                                                </td>
                                                <td>{{ $backup['size'] }}</td>
                                                <td>{{ $backup['date'] }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="/admin/backups/download/{{ $backup['name'] }}" 
                                                           class="btn btn-success" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button class="btn btn-info" 
                                                                onclick="restoreSettings('{{ $backup['name'] }}')" 
                                                                title="Restore">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                        <button class="btn btn-danger" 
                                                                onclick="deleteBackup('{{ $backup['name'] }}')" 
                                                                title="Delete">
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
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
                                <h6 class="text-gray-600">No Settings Backups Found</h6>
                                <p class="text-gray-500">Create your first settings backup.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus mr-2"></i>Create New Backup
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="createBackupForm" method="POST" action="/admin/backups/create">
                    @csrf
                    <div class="form-group">
                        <label>Select Backup Type:</label>
                        <div class="backup-options">
                            <div class="backup-option" onclick="selectBackupType('db')">
                                <div class="backup-icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="backup-info">
                                    <h6>Database Backup</h6>
                                    <p class="text-muted">Backup all database tables and data</p>
                                </div>
                                <div class="backup-radio">
                                    <input type="radio" name="type" value="db" id="type_db">
                                </div>
                            </div>
                            
                            <div class="backup-option" onclick="selectBackupType('files')">
                                <div class="backup-icon">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="backup-info">
                                    <h6>Files Backup</h6>
                                    <p class="text-muted">Backup application files and assets</p>
                                </div>
                                <div class="backup-radio">
                                    <input type="radio" name="type" value="files" id="type_files">
                                </div>
                            </div>
                            
                            <div class="backup-option" onclick="selectBackupType('settings')">
                                <div class="backup-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="backup-info">
                                    <h6>Settings Backup</h6>
                                    <p class="text-muted">Backup system settings only</p>
                                </div>
                                <div class="backup-radio">
                                    <input type="radio" name="type" value="settings" id="type_settings">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> Database and files backups may take several minutes to complete depending on your data size.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createBackup()" id="createBackupBtn" disabled>
                    <i class="fas fa-play mr-1"></i>Create Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trash mr-2"></i>Delete Backup
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                <p>Are you sure you want to delete this backup file?</p>
                <p><strong id="deleteBackupName"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i>Delete Backup
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Settings Restore Modal -->
<div class="modal fade" id="restoreSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo mr-2"></i>Restore Settings
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Warning:</strong> This will overwrite all current settings with the backup data. This action cannot be undone.
                </div>
                <p>Are you sure you want to restore settings from this backup?</p>
                <p><strong id="restoreBackupName"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="restoreSettingsForm" method="POST" action="/admin/backups/restore-settings" enctype="multipart/form-data" class="d-inline">
                    @csrf
                    <input type="hidden" id="restoreFileName" name="backup_filename">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo mr-1"></i>Restore Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.backup-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.backup-option {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #e3e6f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.backup-option:hover {
    border-color: #4e73df;
    background-color: #f8f9fc;
}

.backup-option.selected {
    border-color: #4e73df;
    background-color: #eef2ff;
}

.backup-icon {
    font-size: 24px;
    color: #5a5c69;
    margin-right: 15px;
    width: 40px;
    text-align: center;
}

.backup-option.selected .backup-icon {
    color: #4e73df;
}

.backup-info {
    flex: 1;
}

.backup-info h6 {
    margin: 0;
    font-weight: 600;
}

.backup-info p {
    margin: 0;
    font-size: 14px;
}

.backup-radio {
    margin-left: 15px;
}

.nav-tabs .nav-link {
    border: none;
    color: #5a5c69;
}

.nav-tabs .nav-link.active {
    border-bottom: 2px solid #4e73df;
    color: #4e73df;
    background: none;
}
</style>
@endpush

@push('scripts')
<script>
let selectedBackupType = null;

function showCreateBackupModal() {
    $('#createBackupModal').modal('show');
}

function selectBackupType(type) {
    // Remove previous selection
    $('.backup-option').removeClass('selected');
    $('input[name="type"]').prop('checked', false);
    
    // Select new type
    $(`.backup-option`).each(function() {
        if ($(this).find(`input[value="${type}"]`).length > 0) {
            $(this).addClass('selected');
            $(this).find('input').prop('checked', true);
        }
    });
    
    selectedBackupType = type;
    $('#createBackupBtn').prop('disabled', false);
}

function createBackup() {
    if (!selectedBackupType) {
        alert('Please select a backup type.');
        return;
    }
    
    // Show loading state
    $('#createBackupBtn').html('<i class="fas fa-spinner fa-spin mr-1"></i>Creating...').prop('disabled', true);
    
    // Submit form
    $('#createBackupForm').submit();
}

function deleteBackup(filename) {
    $('#deleteBackupName').text(filename);
    $('#deleteForm').attr('action', `/admin/backups/destroy/${filename}`);
    $('#deleteModal').modal('show');
}

function restoreSettings(filename) {
    $('#restoreBackupName').text(filename);
    $('#restoreFileName').val(filename);
    $('#restoreSettingsModal').modal('show');
}

function refreshBackups() {
    location.reload();
}

function testBackup() {
    $.ajax({
        url: '/admin/backups/test',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            type: 'db'
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Backup system test passed!');
            } else {
                showAlert('error', 'Backup system test failed: ' + response.message);
            }
        },
        error: function() {
            showAlert('error', 'Failed to test backup system.');
        }
    });
}

function cleanupBackups() {
    if (confirm('Are you sure you want to cleanup old backups based on retention settings?')) {
        $.ajax({
            url: '/admin/backups/cleanup',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                showAlert('success', 'Backup cleanup completed successfully!');
                setTimeout(() => location.reload(), 1500);
            },
            error: function() {
                showAlert('error', 'Failed to cleanup backups.');
            }
        });
    }
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${icon} mr-2"></i>${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.alert').remove();
    $(alert).insertAfter('.mb-4:first');
}

// Toggle notification email field
$('#backup_notifications').change(function() {
    if ($(this).is(':checked')) {
        $('#notification_email_group').show();
    } else {
        $('#notification_email_group').hide();
    }
});

// Auto-save settings on change
$('#backupSettingsForm input, #backupSettingsForm select').change(function() {
    $('#backupSettingsForm').submit();
});

// Initialize DataTables
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#applicationBackupsTable, #settingsBackupsTable').DataTable({
            "order": [[ 3, "desc" ]], // Sort by date
            "pageLength": 10,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": 4 } // Disable sorting on Actions column
            ]
        });
    }
});
</script>
@endpush