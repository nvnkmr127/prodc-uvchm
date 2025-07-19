@extends('layouts.theme')

@section('title', 'Dashboard Builder')

@push('styles')
<style>
    .dashboard-builder {
        background: #f9fafb;
    }
    
    .widget-item {
        padding: 0.75rem;
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        text-align: center;
        cursor: grab;
        background: white;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
    }
    
    .widget-item:hover {
        border-color: #3b82f6;
        background: #eff6ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .grid-canvas {
        min-height: 600px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        position: relative;
        background-image: 
            linear-gradient(rgba(0,0,0,0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,0,0,0.05) 1px, transparent 1px);
        background-size: 20px 20px;
    }
    
    .dashboard-widget {
        position: absolute;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        min-width: 200px;
        min-height: 150px;
        cursor: move;
    }
    
    .dashboard-widget:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: #3b82f6;
    }
    
    .widget-header {
        padding: 8px 12px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .widget-content {
        padding: 16px;
    }
    
    .delete-btn {
        width: 20px;
        height: 20px;
        border: none;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .delete-btn:hover {
        background: #dc2626;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }
    
    .template-item {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #e3e6f0;
}

.template-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
    transform: translateY(-1px);
}

.template-item .card-title {
    color: #374151;
    font-size: 14px;
}

.template-item .badge {
    font-size: 10px;
}

/* Modal Enhancements */
.modal-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.modal-header .modal-title {
    color: #374151;
    font-weight: 600;
}

/* Configuration Form Styles */
#widgetConfigForm .form-label {
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

#widgetConfigForm .form-control,
#widgetConfigForm .form-select {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

#widgetConfigForm .form-control:focus,
#widgetConfigForm .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Auto-save Indicator */
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.auto-save-indicator {
    animation: slideInRight 0.3s ease;
}

/* Enhanced Button States */
.btn.btn-warning {
    background: #f59e0b;
    border-color: #f59e0b;
    color: white;
}

.btn.btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
}

/* Template Category Filter */
#templateCategory {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
}

/* Loading States */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

/* Enhanced Widget States */
.dashboard-widget.configuring {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2) !important;
}

/* Improved resize handle visibility */
.resize-handle {
    transition: all 0.2s ease;
}

.dashboard-widget:hover .resize-handle {
    opacity: 0.7;
}

/* Better visual feedback for drag operations */
.dashboard-widget.dragging {
    opacity: 0.8;
    transform: rotate(2deg) scale(1.02);
    z-index: 1000;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
}

.dashboard-widget {
    user-select: none;
}

.widget-selected {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

.widget-header {
    cursor: move;
    user-select: none;
}

.widget-actions {
    display: flex;
    gap: 4px;
    align-items: center;
}

.action-btn {
    width: 24px;
    height: 24px;
    border: none;
    background: #6b7280;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #4b5563;
    transform: scale(1.1);
}

/* Resize Handles */
.resize-handles {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.resize-handle {
    position: absolute;
    background: #3b82f6;
    border: 2px solid white;
    border-radius: 50%;
    width: 12px;
    height: 12px;
    pointer-events: all;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 10;
}

.widget-selected .resize-handle {
    opacity: 1;
}

.resize-handle:hover {
    transform: scale(1.3);
    opacity: 1 !important;
}

/* Corner handles */
.resize-handle-nw {
    top: -6px;
    left: -6px;
    cursor: nw-resize;
}

.resize-handle-ne {
    top: -6px;
    right: -6px;
    cursor: ne-resize;
}

.resize-handle-sw {
    bottom: -6px;
    left: -6px;
    cursor: sw-resize;
}

.resize-handle-se {
    bottom: -6px;
    right: -6px;
    cursor: se-resize;
}

/* Edge handles */
.resize-handle-n {
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    cursor: n-resize;
}

.resize-handle-s {
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    cursor: s-resize;
}

.resize-handle-e {
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
    cursor: e-resize;
}

.resize-handle-w {
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    cursor: w-resize;
}

/* Grid canvas enhancements */
.grid-canvas {
    background-image: 
        radial-gradient(circle, #cbd5e1 1px, transparent 1px);
    background-size: 20px 20px;
}

/* Keyboard shortcuts help */
.keyboard-shortcuts {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 1000;
}

.keyboard-shortcuts.show {
    opacity: 1;
}

/* Widget content improvements */
.widget-content {
    overflow: hidden;
    height: calc(100% - 35px);
}

/* Animation improvements */
.dashboard-widget {
    transition: border-color 0.2s, box-shadow 0.2s;
}

.widget-item {
    transform: translateZ(0); /* Enable hardware acceleration */
}

/* Preview mode adjustments */
.preview-mode .resize-handle,
.preview-mode .widget-actions,
.preview-mode .delete-btn {
    display: none !important;
}

.preview-mode .widget-header {
    cursor: default !important;
}

.preview-mode .dashboard-widget {
    cursor: default !important;
}
</style>
@endpush

@section('content')
<div class="d-flex gap-2">
    <select id="roleSelect" class="form-select">
        <option value="">Select Role</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
        @endforeach
    </select>
    
    <button id="templatesBtn" onclick="toggleTemplates()" class="btn btn-outline-primary">
        <i class="fas fa-layer-group"></i> Templates
    </button>
    
    <button id="previewBtn" onclick="togglePreview()" class="btn btn-primary">
        <i class="fas fa-eye"></i> Preview
    </button>
    
    <button id="saveBtn" onclick="saveDashboard()" class="btn btn-success">
        <i class="fas fa-save"></i> Save Layout
    </button>
</div>

<div class="row">
    <!-- Widget Sidebar -->
   <!-- Replace the widget sidebar content -->
<div class="card-body">
    <div class="mb-4">
        <h6 class="text-muted mb-2">Analytics & Charts</h6>
        
        <div class="widget-item" draggable="true" data-widget="chart">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">📊</div>
            <small class="font-weight-bold">Basic Chart</small>
        </div>
        
        <div class="widget-item" draggable="true" data-widget="advanced_chart">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">📈</div>
            <small class="font-weight-bold">Advanced Chart</small>
        </div>
        
        <div class="widget-item" draggable="true" data-widget="kpi">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">📊</div>
            <small class="font-weight-bold">KPI Metrics</small>
        </div>

        <div class="widget-item" draggable="true" data-widget="table">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">📋</div>
            <small class="font-weight-bold">Data Table</small>
        </div>
    </div>
    
    <div class="mb-4">
        <h6 class="text-muted mb-2">Academic</h6>
        
        <div class="widget-item" draggable="true" data-widget="students">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">👥</div>
            <small class="font-weight-bold">Students</small>
        </div>
        
        <div class="widget-item" draggable="true" data-widget="calendar">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">📅</div>
            <small class="font-weight-bold">Calendar</small>
        </div>
    </div>

    <div class="mb-4">
        <h6 class="text-muted mb-2">Campus & Utilities</h6>
        
        <div class="widget-item" draggable="true" data-widget="map">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">🗺️</div>
            <small class="font-weight-bold">Campus Map</small>
        </div>
        
        <div class="widget-item" draggable="true" data-widget="files">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">📁</div>
            <small class="font-weight-bold">File Manager</small>
        </div>
    </div>

    <div class="mb-4">
        <h6 class="text-muted mb-2">Financial</h6>
        
        <div class="widget-item" draggable="true" data-widget="revenue">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">💰</div>
            <small class="font-weight-bold">Revenue</small>
        </div>
        
        <div class="widget-item" draggable="true" data-widget="fees">
            <div style="font-size: 2rem; margin-bottom: 0.25rem;">💳</div>
            <small class="font-weight-bold">Fee Status</small>
        </div>
    </div>
</div>
<!-- Add Templates Panel after Widget Sidebar -->
<div class="col-lg-3" id="templatesPanel" style="display: none;">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Dashboard Templates</h6>
        </div>
        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
            <div class="mb-3">
                <select id="templateCategory" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <option value="academic">Academic</option>
                    <option value="financial">Financial</option>
                    <option value="analytics">Analytics</option>
                    <option value="executive">Executive</option>
                </select>
            </div>
            
            <div id="templatesList">
                <!-- Templates will be loaded here -->
            </div>
            
            <div class="mt-3">
                <button class="btn btn-success btn-sm w-100" onclick="showSaveTemplateModal()">
                    <i class="fas fa-save"></i> Save Current as Template
                </button>
            </div>
        </div>
    </div>
</div>
    <!-- Main Canvas -->
    <div class="col-lg-9">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Dashboard Canvas - <span id="selectedRoleName" class="text-info">None Selected</span>
                </h6>
            </div>
            <div class="card-body">
                <div id="gridCanvas" class="grid-canvas">
                    <!-- Drop Zone Indicator -->
                    <div id="dropIndicator" style="position: absolute; border: 2px dashed #3b82f6; background: rgba(59, 130, 246, 0.1); border-radius: 0.5rem; padding: 1rem; text-align: center; color: #3b82f6; font-weight: 500; display: none; pointer-events: none;">
                        Drop widget here
                    </div>
                    
                    <!-- Empty State -->
                    <div id="emptyState" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #6b7280;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📊</div>
                        <h4 class="font-weight-bold mb-2">No widgets added yet</h4>
                        <p>Drag widgets from the sidebar to get started</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Widget Configuration Modal -->
<div class="modal fade" id="widgetConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog"></i> Configure Widget
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="widgetConfigForm">
                    <!-- Configuration form will be generated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveWidgetConfig()">Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<!-- Save Template Modal -->
<div class="modal fade" id="saveTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-save"></i> Save Dashboard Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="saveTemplateForm">
                    <div class="mb-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" class="form-control" id="templateName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="templateDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="templateCategorySelect" required>
                            <option value="academic">Academic</option>
                            <option value="financial">Financial</option>
                            <option value="analytics">Analytics</option>
                            <option value="executive">Executive</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="templatePublic">
                        <label class="form-check-label" for="templatePublic">
                            Make this template public (visible to other users)
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">Save Template</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
// Enhanced Dashboard Builder with Resizing
let selectedRole = null;
let isPreview = false;
let widgets = [];
let draggedWidget = null;
let selectedWidget = null;

// Grid configuration
const GRID_SIZE = 20;
const MIN_WIDGET_WIDTH = 200;
const MIN_WIDGET_HEIGHT = 150;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Enhanced Dashboard Builder initialized');
    initializeDragAndDrop();
    setupEventListeners();
    setupKeyboardShortcuts();
});

function setupEventListeners() {
    // Role selection
    document.getElementById('roleSelect').addEventListener('change', function() {
        const roleId = this.value;
        const roleName = this.options[this.selectedIndex].text;
        
        if (roleId) {
            selectedRole = { id: roleId, name: roleName };
            document.getElementById('selectedRoleName').textContent = roleName;
            loadDashboardForRole(roleId);
        } else {
            selectedRole = null;
            document.getElementById('selectedRoleName').textContent = 'None Selected';
        }
    });

    // Canvas click to deselect widgets
    document.getElementById('gridCanvas').addEventListener('click', function(e) {
        if (e.target === this) {
            deselectAllWidgets();
        }
    });
}

// Real-time data updates
let realTimeUpdates = false;
let updateInterval;

function enableRealTimeUpdates() {
    if (realTimeUpdates) return;
    
    realTimeUpdates = true;
    updateInterval = setInterval(() => {
        updateAllWidgets();
    }, 30000); // Update every 30 seconds
    
    console.log('Real-time updates enabled');
}

function disableRealTimeUpdates() {
    if (!realTimeUpdates) return;
    
    realTimeUpdates = false;
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
    
    console.log('Real-time updates disabled');
}

function updateAllWidgets() {
    if (!selectedRole || widgets.length === 0) return;
    
    console.log('Updating all widgets with real-time data...');
    
    widgets.forEach(widget => {
        if (widget.widgetId) {
            loadWidgetData(widget.widgetId, widget.id, true); // true for silent update
        }
    });
}

// Enhanced loadWidgetData function with real-time support
function loadWidgetData(widgetId, instanceId, silent = false) {
    if (!silent) {
        const contentDiv = document.getElementById(`widget-content-${instanceId}`);
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0">Loading data...</p>
                </div>
            `;
        }
    }

    fetch(`/api/dashboard-builder/widgets/${widgetId}/data?t=${Date.now()}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const contentDiv = document.getElementById(`widget-content-${instanceId}`);
        if (contentDiv) {
            renderWidgetContent(contentDiv, data, instanceId);
        }
        
        // Show real-time update indicator
        if (silent && realTimeUpdates) {
            showRealTimeUpdateIndicator(instanceId);
        }
    })
    .catch(error => {
        console.error('Widget data load error:', error);
        if (!silent) {
            const contentDiv = document.getElementById(`widget-content-${instanceId}`);
            if (contentDiv) {
                contentDiv.innerHTML = '<p class="text-danger">Failed to load data</p>';
            }
        }
    });
}

function showRealTimeUpdateIndicator(instanceId) {
    const widget = widgets.find(w => w.id === instanceId);
    if (!widget) return;
    
    // Add a small pulse indicator
    const indicator = document.createElement('div');
    indicator.className = 'real-time-indicator';
    indicator.innerHTML = '<i class="fas fa-circle text-success"></i>';
    indicator.style.cssText = `
        position: absolute;
        top: 8px;
        right: 40px;
        z-index: 10;
        opacity: 0;
        animation: pulseIn 0.5s ease;
    `;
    
    widget.element.appendChild(indicator);
    
    // Remove after animation
    setTimeout(() => {
        indicator.style.animation = 'pulseOut 0.5s ease';
        setTimeout(() => indicator.remove(), 500);
    }, 2000);
}

// Add real-time toggle to header
function addRealTimeToggle() {
    const headerActions = document.querySelector('.d-flex.gap-2');
    if (headerActions && !document.getElementById('realTimeToggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'realTimeToggle';
        toggleBtn.className = 'btn btn-outline-info';
        toggleBtn.innerHTML = '<i class="fas fa-broadcast-tower"></i> Real-time';
        toggleBtn.onclick = toggleRealTime;
        
        headerActions.insertBefore(toggleBtn, headerActions.firstChild);
    }
}

function toggleRealTime() {
    const btn = document.getElementById('realTimeToggle');
    if (realTimeUpdates) {
        disableRealTimeUpdates();
        btn.className = 'btn btn-outline-info';
        btn.innerHTML = '<i class="fas fa-broadcast-tower"></i> Real-time';
    } else {
        enableRealTimeUpdates();
        btn.className = 'btn btn-info';
        btn.innerHTML = '<i class="fas fa-broadcast-tower"></i> Real-time ON';
    }
}

// Add CSS for real-time animations
const realTimeStyles = document.createElement('style');
realTimeStyles.textContent = `
    @keyframes pulseIn {
        from { opacity: 0; transform: scale(0.5); }
        to { opacity: 1; transform: scale(1); }
    }
    
    @keyframes pulseOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.5); }
    }
    
    .real-time-indicator {
        font-size: 8px;
    }
    
    .widget-updated {
        animation: highlightUpdate 1s ease;
    }
    
    @keyframes highlightUpdate {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
`;
document.head.appendChild(realTimeStyles);

// Initialize real-time toggle when page loads
document.addEventListener('DOMContentLoaded', function() {
    // ... existing initialization code ...
    setTimeout(addRealTimeToggle, 1000); // Add after other elements are loaded
});

// Update the getWidgetConfig function
function getWidgetConfig(type) {
    const configs = {
        chart: { 
            name: 'Chart Widget', 
            icon: '📊', 
            description: 'Interactive charts and graphs',
            component: 'ChartWidget'
        },
        advanced_chart: { 
            name: 'Advanced Chart', 
            icon: '📈', 
            description: 'Advanced Chart.js visualization',
            component: 'AdvancedChartWidget'
        },
        kpi: { 
            name: 'KPI Widget', 
            icon: '📈', 
            description: 'Key performance indicators',
            component: 'KPIWidget'
        },
        table: { 
            name: 'Table Widget', 
            icon: '📋', 
            description: 'Data tables with sorting',
            component: 'DataTableWidget'
        },
        calendar: { 
            name: 'Calendar Widget', 
            icon: '📅', 
            description: 'Academic calendar and events',
            component: 'CalendarWidget'
        },
        map: { 
            name: 'Map Widget', 
            icon: '🗺️', 
            description: 'Interactive campus map',
            component: 'MapWidget'
        },
        files: { 
            name: 'File Manager', 
            icon: '📁', 
            description: 'File and document management',
            component: 'FileManagerWidget'
        },
        students: { 
            name: 'Students Widget', 
            icon: '👥', 
            description: 'Student information',
            component: 'StudentsWidget'
        },
        revenue: { 
            name: 'Revenue Widget', 
            icon: '💰', 
            description: 'Revenue analytics',
            component: 'RevenueWidget'
        },
fees: { 
           name: 'Fee Status Widget', 
           icon: '💳', 
           description: 'Fee collection status',
           component: 'FeeStatusWidget'
       }
   };
   return configs[type] || { 
       name: 'Unknown Widget', 
       icon: '❓', 
       description: 'Unknown widget type',
       component: 'ErrorWidget'
   };
}

// Update widget ID mapping
function getWidgetIdByType(type) {
   const widgetMap = {
       'chart': 1,
       'advanced_chart': 8,
       'kpi': 2,
       'table': 3,
       'students': 4,
       'calendar': 5,
       'map': 9,
       'files': 10,
       'revenue': 6,
       'fees': 7
   };
   return widgetMap[type] || 1;
}

// Template Management
let currentConfigWidget = null;

function toggleTemplates() {
    const templatesPanel = document.getElementById('templatesPanel');
    const widgetSidebar = document.getElementById('widgetSidebar');
    const templatesBtn = document.getElementById('templatesBtn');
    
    if (templatesPanel.style.display === 'none') {
        // Show templates, hide widgets
        templatesPanel.style.display = 'block';
        widgetSidebar.style.display = 'none';
        templatesBtn.innerHTML = '<i class="fas fa-th-large"></i> Widgets';
        templatesBtn.classList.remove('btn-outline-primary');
        templatesBtn.classList.add('btn-primary');
        
        loadTemplates();
    } else {
        // Show widgets, hide templates
        templatesPanel.style.display = 'none';
        widgetSidebar.style.display = 'block';
        templatesBtn.innerHTML = '<i class="fas fa-layer-group"></i> Templates';
        templatesBtn.classList.remove('btn-primary');
        templatesBtn.classList.add('btn-outline-primary');
    }
}

function loadTemplates() {
    const templatesList = document.getElementById('templatesList');
    templatesList.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading templates...</div>';
    
    fetch('/api/dashboard-builder/templates', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        renderTemplates(data.templates);
    })
    .catch(error => {
        console.error('Error loading templates:', error);
        templatesList.innerHTML = '<div class="text-danger">Failed to load templates</div>';
    });
}

function renderTemplates(templates) {
    const templatesList = document.getElementById('templatesList');
    
    if (templates.length === 0) {
        templatesList.innerHTML = '<div class="text-muted text-center">No templates available</div>';
        return;
    }
    
    const templatesHtml = templates.map(template => `
        <div class="template-item card mb-2" data-template-id="${template.id}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-1">${template.name}</h6>
                    <span class="badge bg-secondary">${template.category}</span>
                </div>
                <p class="card-text small text-muted mb-2">${template.description || 'No description'}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        ${template.widget_count} widgets • Used ${template.usage_count} times
                    </small>
                    <button class="btn btn-primary btn-sm" onclick="applyTemplate(${template.id})">
                        Apply
                    </button>
                </div>
                <div class="mt-1">
                    <small class="text-muted">By ${template.created_by} • ${template.created_at}</small>
                </div>
            </div>
        </div>
    `).join('');
    
    templatesList.innerHTML = templatesHtml;
}

function applyTemplate(templateId) {
    if (!selectedRole || !selectedRole.dashboard_id) {
        alert('Please select a role first');
        return;
    }
    
    if (widgets.length > 0) {
        if (!confirm('This will replace your current dashboard layout. Continue?')) {
            return;
        }
    }
    
    fetch(`/api/dashboard-builder/templates/${templateId}/apply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            template_id: templateId,
            dashboard_id: selectedRole.dashboard_id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            showSuccessMessage(data.message);
            // Reload the dashboard
            loadDashboardForRole(selectedRole.id);
        }
    })
    .catch(error => {
        console.error('Error applying template:', error);
        alert('Failed to apply template');
    });
}

function showSaveTemplateModal() {
    if (!selectedRole || widgets.length === 0) {
        alert('Please add some widgets to your dashboard first');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('saveTemplateModal'));
    modal.show();
}

function saveTemplate() {
    const form = document.getElementById('saveTemplateForm');
    const formData = new FormData(form);
    
    const templateData = {
        dashboard_id: selectedRole.dashboard_id,
        name: document.getElementById('templateName').value,
        description: document.getElementById('templateDescription').value,
        category: document.getElementById('templateCategorySelect').value,
        is_public: document.getElementById('templatePublic').checked
    };
    
    if (!templateData.name) {
        alert('Please enter a template name');
        return;
    }
    
    fetch('/api/dashboard-builder/templates', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(templateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            showSuccessMessage(data.message);
            bootstrap.Modal.getInstance(document.getElementById('saveTemplateModal')).hide();
            form.reset();
            // Reload templates if panel is open
            if (document.getElementById('templatesPanel').style.display !== 'none') {
                loadTemplates();
            }
        }
    })
    .catch(error => {
        console.error('Error saving template:', error);
        alert('Failed to save template');
    });
}

// Widget Configuration
function configureWidget(button) {
    const widget = button.closest('.dashboard-widget');
    const widgetData = widgets.find(w => w.element === widget);
    
    if (!widgetData) return;
    
    currentConfigWidget = widgetData;
    
    // Get widget configuration schema
    fetch(`/api/dashboard-builder/widgets/${getWidgetIdByType(widgetData.type)}/config`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        renderWidgetConfigForm(data);
        const modal = new bootstrap.Modal(document.getElementById('widgetConfigModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error loading widget config:', error);
        alert('Failed to load widget configuration');
    });
}

function renderWidgetConfigForm(configData) {
    const form = document.getElementById('widgetConfigForm');
    const schema = configData.schema;
    const currentConfig = configData.current_config;
    
    let formHtml = `<h6>${configData.widget.name} Configuration</h6><hr>`;
    
    Object.entries(schema).forEach(([key, field]) => {
        const currentValue = currentConfig[key] || field.default;
        
        formHtml += `<div class="mb-3">`;
        formHtml += `<label class="form-label">${field.label}</label>`;
        
        switch (field.type) {
            case 'text':
                formHtml += `<input type="text" class="form-control" id="config_${key}" value="${currentValue || ''}" placeholder="${field.placeholder || ''}">`;
                break;
            case 'number':
                formHtml += `<input type="number" class="form-control" id="config_${key}" value="${currentValue || field.default}" min="${field.min || ''}" max="${field.max || ''}">`;
                break;
            case 'boolean':
                formHtml += `<div class="form-check">
                    <input class="form-check-input" type="checkbox" id="config_${key}" ${currentValue ? 'checked' : ''}>
                    <label class="form-check-label" for="config_${key}">${field.label}</label>
                </div>`;
                break;
            case 'select':
                formHtml += `<select class="form-select" id="config_${key}">`;
                Object.entries(field.options).forEach(([value, label]) => {
                    formHtml += `<option value="${value}" ${currentValue === value ? 'selected' : ''}>${label}</option>`;
                });
                formHtml += `</select>`;
                break;
            case 'textarea':
                formHtml += `<textarea class="form-control" id="config_${key}" rows="3">${currentValue || ''}</textarea>`;
                break;
        }
        
        if (field.description) {
            formHtml += `<div class="form-text">${field.description}</div>`;
        }
        
        formHtml += `</div>`;
    });
    
    form.innerHTML = formHtml;
}

function saveWidgetConfig() {
    if (!currentConfigWidget) return;
    
    const form = document.getElementById('widgetConfigForm');
    const formElements = form.querySelectorAll('input, select, textarea');
    const config = {};
    
    formElements.forEach(element => {
        const key = element.id.replace('config_', '');
        if (element.type === 'checkbox') {
            config[key] = element.checked;
        } else if (element.type === 'number') {
            config[key] = parseFloat(element.value) || 0;
        } else {
            config[key] = element.value;
        }
    });
    
    // Update widget configuration
    fetch('/api/dashboard-builder/widgets/config', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            instance_id: currentConfigWidget.id,
            config: config
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            showSuccessMessage(data.message);
            bootstrap.Modal.getInstance(document.getElementById('widgetConfigModal')).hide();
            
            // Update widget display if needed
            updateWidgetDisplay(currentConfigWidget, config);
        }
    })
    .catch(error => {
        console.error('Error saving widget config:', error);
        alert('Failed to save widget configuration');
    });
}

function updateWidgetDisplay(widgetData, newConfig) {
    // Update widget header if title changed
    if (newConfig.title) {
        const header = widgetData.element.querySelector('.widget-header span');
        if (header) {
            header.textContent = newConfig.title;
        }
    }
    
    // Reload widget data to apply new configuration
    if (widgetData.widgetId) {
        loadWidgetData(widgetData.widgetId, widgetData.id);
    }
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (selectedWidget) {
            switch(e.key) {
                case 'Delete':
                case 'Backspace':
                    e.preventDefault();
                    removeWidget(selectedWidget.querySelector('.delete-btn'));
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    moveWidget(selectedWidget, 0, -GRID_SIZE);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    moveWidget(selectedWidget, 0, GRID_SIZE);
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    moveWidget(selectedWidget, -GRID_SIZE, 0);
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    moveWidget(selectedWidget, GRID_SIZE, 0);
                    break;
                case 'Escape':
                    deselectAllWidgets();
                    break;
            }
        }
        
        // Global shortcuts
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    saveDashboard();
                    break;
                case 'z':
                    e.preventDefault();
                    // TODO: Implement undo
                    break;
            }
        }
    });
}

function initializeDragAndDrop() {
    const canvas = document.getElementById('gridCanvas');
    const widgetItems = document.querySelectorAll('.widget-item');
    
    // Make widget items draggable
    widgetItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedWidget = this.dataset.widget;
            console.log('Started dragging:', draggedWidget);
            
            // Create drag image
            const dragImage = this.cloneNode(true);
            dragImage.style.transform = 'rotate(5deg) scale(0.9)';
            dragImage.style.opacity = '0.8';
            document.body.appendChild(dragImage);
            dragImage.style.position = 'absolute';
            dragImage.style.top = '-1000px';
            
            e.dataTransfer.setDragImage(dragImage, 50, 25);
            
            setTimeout(() => document.body.removeChild(dragImage), 0);
        });
        
        item.addEventListener('dragend', function() {
            draggedWidget = null;
        });
    });
    
    // Canvas drop zone
    canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        if (draggedWidget) {
            showDropIndicator(e);
        }
    });
    
    canvas.addEventListener('dragleave', function(e) {
        if (!canvas.contains(e.relatedTarget)) {
            hideDropIndicator();
        }
    });
    
    canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        hideDropIndicator();
        
        if (draggedWidget) {
            const rect = canvas.getBoundingClientRect();
            const x = snapToGrid(e.clientX - rect.left);
            const y = snapToGrid(e.clientY - rect.top);
            
            addWidgetToCanvas(draggedWidget, x, y);
        }
    });
}

function snapToGrid(value) {
    return Math.round(value / GRID_SIZE) * GRID_SIZE;
}

function showDropIndicator(e) {
    const indicator = document.getElementById('dropIndicator');
    const canvas = document.getElementById('gridCanvas');
    const rect = canvas.getBoundingClientRect();
    
    const x = snapToGrid(e.clientX - rect.left - 100);
    const y = snapToGrid(e.clientY - rect.top - 50);
    
    indicator.style.display = 'block';
    indicator.style.left = Math.max(0, x) + 'px';
    indicator.style.top = Math.max(0, y) + 'px';
    indicator.style.width = '200px';
    indicator.style.height = '160px';
}

function hideDropIndicator() {
    document.getElementById('dropIndicator').style.display = 'none';
}

function addWidgetToCanvas(widgetType, x, y) {
    console.log('Adding widget:', widgetType, 'at', x, y);
    
    // Hide empty state
    document.getElementById('emptyState').style.display = 'none';
    
    // Create widget element
    const widget = document.createElement('div');
    widget.className = 'dashboard-widget';
    widget.style.cssText = `
        position: absolute;
        left: ${x}px;
        top: ${y}px;
        width: 280px;
        height: 200px;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: move;
        z-index: 1;
        transition: all 0.2s ease;
    `;
    
    const widgetConfig = getWidgetConfig(widgetType);
    const instanceId = Date.now();
    
    widget.innerHTML = `
        <div class="widget-header">
            <span>${widgetConfig.name}</span>
            <div class="widget-actions">
                <button onclick="duplicateWidget(this)" class="action-btn" title="Duplicate">
                    <i class="fas fa-copy"></i>
                </button>
                <button onclick="configureWidget(this)" class="action-btn" title="Configure">
                    <i class="fas fa-cog"></i>
                </button>
                <button onclick="removeWidget(this)" class="delete-btn" title="Delete">×</button>
            </div>
        </div>
        <div class="widget-content">
            <div id="widget-content-${instanceId}" class="text-center h-100 d-flex align-items-center justify-content-center">
                <div>
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">${widgetConfig.icon}</div>
                    <p class="mb-1"><strong>${widgetConfig.name}</strong></p>
                    <small class="text-muted">${widgetConfig.description}</small>
                </div>
            </div>
        </div>
        
        <!-- Resize Handles -->
        <div class="resize-handles">
            <div class="resize-handle resize-handle-n" data-direction="n"></div>
            <div class="resize-handle resize-handle-s" data-direction="s"></div>
            <div class="resize-handle resize-handle-e" data-direction="e"></div>
            <div class="resize-handle resize-handle-w" data-direction="w"></div>
            <div class="resize-handle resize-handle-ne" data-direction="ne"></div>
            <div class="resize-handle resize-handle-nw" data-direction="nw"></div>
            <div class="resize-handle resize-handle-se" data-direction="se"></div>
            <div class="resize-handle resize-handle-sw" data-direction="sw"></div>
        </div>
    `;
    
    // Make widget interactive
    makeWidgetInteractive(widget);
    
    document.getElementById('gridCanvas').appendChild(widget);
    
    // Add to widgets array
    const widgetData = {
        id: instanceId,
        type: widgetType,
        x: x,
        y: y,
        width: 280,
        height: 200,
        element: widget
    };
    widgets.push(widgetData);
    
    // Select the new widget
    selectWidget(widget);
    
    console.log('Widget added. Total widgets:', widgets.length);
    
    // Load widget data if it has a real widget ID
    const widgetId = getWidgetIdByType(widgetType);
    if (widgetId) {
        loadWidgetData(widgetId, instanceId);
    }
}

function makeWidgetInteractive(element) {
    // Make draggable
    makeWidgetDraggable(element);
    
    // Make resizable
    makeWidgetResizable(element);
    
    // Make selectable
    element.addEventListener('click', function(e) {
        e.stopPropagation();
        selectWidget(element);
    });
}

function makeWidgetDraggable(element) {
    let isDragging = false;
    let startX, startY, initialX, initialY;
    
    const header = element.querySelector('.widget-header');
    
    header.addEventListener('mousedown', function(e) {
        if (e.target.closest('button')) return; // Don't drag when clicking buttons
        
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        initialX = element.offsetLeft;
        initialY = element.offsetTop;
        
        element.style.zIndex = '1000';
        element.style.transform = 'scale(1.02)';
        element.style.boxShadow = '0 8px 25px rgba(0,0,0,0.2)';
        
        document.addEventListener('mousemove', dragWidget);
        document.addEventListener('mouseup', stopDragging);
        
        e.preventDefault();
    });
    
    function dragWidget(e) {
        if (!isDragging) return;
        
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        const newX = snapToGrid(Math.max(0, initialX + dx));
        const newY = snapToGrid(Math.max(0, initialY + dy));
        
        element.style.left = newX + 'px';
        element.style.top = newY + 'px';
        
        updateWidgetPosition(element, newX, newY);
    }
    
    function stopDragging() {
        isDragging = false;
        element.style.zIndex = '1';
        element.style.transform = 'scale(1)';
        element.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        
        document.removeEventListener('mousemove', dragWidget);
        document.removeEventListener('mouseup', stopDragging);
    }
}

function makeWidgetResizable(element) {
    const resizeHandles = element.querySelectorAll('.resize-handle');
    
    resizeHandles.forEach(handle => {
        handle.addEventListener('mousedown', function(e) {
            e.stopPropagation();
            
            const direction = handle.dataset.direction;
            const startX = e.clientX;
            const startY = e.clientY;
            const startWidth = element.offsetWidth;
            const startHeight = element.offsetHeight;
            const startLeft = element.offsetLeft;
            const startTop = element.offsetTop;
            
            element.style.zIndex = '1000';
            
            function resize(e) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                
                let newWidth = startWidth;
                let newHeight = startHeight;
                let newLeft = startLeft;
                let newTop = startTop;
                
                // Calculate new dimensions based on direction
                switch(direction) {
                    case 'se':
                        newWidth = Math.max(MIN_WIDGET_WIDTH, startWidth + dx);
                        newHeight = Math.max(MIN_WIDGET_HEIGHT, startHeight + dy);
                        break;
                    case 'sw':
                        newWidth = Math.max(MIN_WIDGET_WIDTH, startWidth - dx);
                        newHeight = Math.max(MIN_WIDGET_HEIGHT, startHeight + dy);
                        newLeft = startLeft + (startWidth - newWidth);
                        break;
                    case 'ne':
                        newWidth = Math.max(MIN_WIDGET_WIDTH, startWidth + dx);
                        newHeight = Math.max(MIN_WIDGET_HEIGHT, startHeight - dy);
                        newTop = startTop + (startHeight - newHeight);
                        break;
                    case 'nw':
                        newWidth = Math.max(MIN_WIDGET_WIDTH, startWidth - dx);
                        newHeight = Math.max(MIN_WIDGET_HEIGHT, startHeight - dy);
                        newLeft = startLeft + (startWidth - newWidth);
                        newTop = startTop + (startHeight - newHeight);
                        break;
                    case 'e':
                        newWidth = Math.max(MIN_WIDGET_WIDTH, startWidth + dx);
                        break;
                    case 'w':
                        newWidth = Math.max(MIN_WIDGET_WIDTH, startWidth - dx);
                        newLeft = startLeft + (startWidth - newWidth);
                        break;
                    case 's':
                        newHeight = Math.max(MIN_WIDGET_HEIGHT, startHeight + dy);
                        break;
                    case 'n':
                        newHeight = Math.max(MIN_WIDGET_HEIGHT, startHeight - dy);
                        newTop = startTop + (startHeight - newHeight);
                        break;
                }
                
                // Snap to grid
                newWidth = snapToGrid(newWidth);
                newHeight = snapToGrid(newHeight);
                newLeft = snapToGrid(Math.max(0, newLeft));
                newTop = snapToGrid(Math.max(0, newTop));
                
                // Apply changes
                element.style.width = newWidth + 'px';
                element.style.height = newHeight + 'px';
                element.style.left = newLeft + 'px';
                element.style.top = newTop + 'px';
                
                updateWidgetDimensions(element, newLeft, newTop, newWidth, newHeight);
            }
            
            function stopResize() {
                element.style.zIndex = '1';
                document.removeEventListener('mousemove', resize);
                document.removeEventListener('mouseup', stopResize);
            }
            
            document.addEventListener('mousemove', resize);
            document.addEventListener('mouseup', stopResize);
        });
    });
}

function selectWidget(element) {
    // Deselect all widgets first
    deselectAllWidgets();
    
    // Select this widget
    element.classList.add('widget-selected');
    selectedWidget = element;
    
    // Show resize handles
    const handles = element.querySelectorAll('.resize-handle');
    handles.forEach(handle => handle.style.display = 'block');
}

function deselectAllWidgets() {
    document.querySelectorAll('.dashboard-widget').forEach(widget => {
        widget.classList.remove('widget-selected');
        // Hide resize handles
        widget.querySelectorAll('.resize-handle').forEach(handle => {
            handle.style.display = 'none';
        });
    });
    selectedWidget = null;
}

function updateWidgetPosition(element, x, y) {
    const widget = widgets.find(w => w.element === element);
    if (widget) {
        widget.x = x;
        widget.y = y;
    }
}

function updateWidgetDimensions(element, x, y, width, height) {
    const widget = widgets.find(w => w.element === element);
    if (widget) {
        widget.x = x;
        widget.y = y;
        widget.width = width;
        widget.height = height;
    }
}

function moveWidget(element, dx, dy) {
    const currentX = element.offsetLeft;
    const currentY = element.offsetTop;
    const newX = snapToGrid(Math.max(0, currentX + dx));
    const newY = snapToGrid(Math.max(0, currentY + dy));
    
    element.style.left = newX + 'px';
    element.style.top = newY + 'px';
    
    updateWidgetPosition(element, newX, newY);
}

function duplicateWidget(button) {
    const widget = button.closest('.dashboard-widget');
    const widgetData = widgets.find(w => w.element === widget);
    
    if (widgetData) {
        // Create duplicate at offset position
        const newX = snapToGrid(widgetData.x + 40);
        const newY = snapToGrid(widgetData.y + 40);
        
        addWidgetToCanvas(widgetData.type, newX, newY);
        showSuccessMessage('Widget duplicated successfully!');
    }
}

function configureWidget(button) {
    const widget = button.closest('.dashboard-widget');
    const widgetData = widgets.find(w => w.element === widget);
    
    if (widgetData) {
        // TODO: Open widget configuration modal
        alert(`Configuration for ${widgetData.type} widget coming soon!`);
    }
}

function removeWidget(button) {
    const widget = button.closest('.dashboard-widget');
    
    // Add removal animation
    widget.style.transition = 'all 0.3s ease';
    widget.style.transform = 'scale(0.8)';
    widget.style.opacity = '0';
    
    setTimeout(() => {
        widget.remove();
        
        // Remove from widgets array
        widgets = widgets.filter(w => w.element !== widget);
        
        // Clear selection if this widget was selected
        if (selectedWidget === widget) {
            selectedWidget = null;
        }
        
        // Show empty state if no widgets left
        if (widgets.length === 0) {
            document.getElementById('emptyState').style.display = 'block';
        }
        
        console.log('Widget removed. Remaining widgets:', widgets.length);
    }, 300);
}

// Keep existing functions: saveDashboard, loadDashboardForRole, etc.
// ... (previous functions remain the same)

function getWidgetConfig(type) {
    const configs = {
        chart: { 
            name: 'Chart Widget', 
            icon: '📊', 
            description: 'Interactive charts and graphs' 
        },
        kpi: { 
            name: 'KPI Widget', 
            icon: '📈', 
            description: 'Key performance indicators' 
        },
        table: { 
            name: 'Table Widget', 
            icon: '📋', 
            description: 'Data tables with sorting' 
        },
        students: { 
            name: 'Students Widget', 
            icon: '👥', 
            description: 'Student information' 
        },
        calendar: { 
            name: 'Calendar Widget', 
            icon: '📅', 
            description: 'Academic calendar' 
        },
        revenue: { 
            name: 'Revenue Widget', 
            icon: '💰', 
            description: 'Revenue analytics' 
        },
        fees: { 
            name: 'Fee Status Widget', 
            icon: '💳', 
            description: 'Fee collection status' 
        }
    };
    return configs[type] || { 
        name: 'Unknown Widget', 
        icon: '❓', 
        description: 'Unknown widget type' 
    };
}

function getWidgetIdByType(type) {
    const widgetMap = {
        'chart': 1,
        'kpi': 2,
        'table': 3,
        'students': 4,
        'calendar': 5,
        'revenue': 6,
        'fees': 7
    };
    return widgetMap[type] || 1;
}

// Add existing functions for save/load/etc...
// (Include all the previous functions: saveDashboard, loadDashboardForRole, etc.)
</script>
@endpush