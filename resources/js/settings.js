/**
 * Settings Management JavaScript Functions
 */

// Advanced settings search
function searchSettings(query) {
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    
    fetch(`/admin/settings/search?query=${encodeURIComponent(query)}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data.data);
    })
    .catch(error => {
        console.error('Search error:', error);
    });
}

function displaySearchResults(results) {
    const container = document.getElementById('searchResults');
    
    if (results.length === 0) {
        container.innerHTML = '<p class="text-muted">No settings found.</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    results.forEach(setting => {
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${setting.key}</h6>
                    <small class="text-muted">${setting.group}</small>
                </div>
                <p class="mb-1">${setting.description || 'No description'}</p>
                <small class="text-muted">Value: ${setting.value || 'Not set'}</small>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Backup management
function createBackup() {
    if (!confirm('Create a backup of current settings?')) return;
    
    fetch('/admin/settings/backup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Backup created: ${data.filename}`);
            loadBackupHistory();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error creating backup: ' + error.message);
    });
}

function loadBackupHistory() {
    fetch('/admin/settings/backups', {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayBackupHistory(data.data);
    })
    .catch(error => {
        console.error('Error loading backup history:', error);
    });
}

function displayBackupHistory(backups) {
    const container = document.getElementById('backupHistory');
    
    if (backups.length === 0) {
        container.innerHTML = '<p class="text-muted">No backups found.</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>Filename</th><th>Size</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
    
    backups.forEach(backup => {
        html += `
            <tr>
                <td>${backup.filename}</td>
                <td>${backup.size}</td>
                <td>${backup.modified_at}</td>
                <td>
                    <a href="/admin/settings/backups/${backup.filename}/download" 
                       class="btn btn-sm btn-outline-primary">Download</a>
                    <button onclick="deleteBackup('${backup.filename}')" 
                            class="btn btn-sm btn-outline-danger">Delete</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function deleteBackup(filename) {
    if (!confirm(`Delete backup ${filename}?`)) return;
    
    fetch(`/admin/settings/backups/${filename}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Backup deleted successfully');
            loadBackupHistory();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error deleting backup: ' + error.message);
    });
}

// Database optimization
function optimizeDatabase() {
    if (!confirm('This will clean up duplicate and invalid settings. Continue?')) return;
    
    fetch('/admin/settings/optimize', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Database optimized: ${JSON.stringify(data.details)}`);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error optimizing database: ' + error.message);
    });
}

// Seed default settings
function seedDefaults() {
    if (!confirm('This will create default settings for any missing configuration. Continue?')) return;
    
    fetch('/admin/settings/seed-defaults', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error seeding defaults: ' + error.message);
    });
}
