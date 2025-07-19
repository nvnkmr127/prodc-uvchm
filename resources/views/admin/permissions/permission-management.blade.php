@extends('layouts.theme')
@section('title', 'Permission Management')

@push('styles')
<style>
    .dashboard-card {
        border: none;
        border-radius: 0.75rem;
box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.2s ease;
}
    
    .dashboard-card:hover {
        transform: translateY(-2px);
box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .stat-card {
        background: linear-gradient(135deg, var(--start-color) 0%, var(--end-color) 100%);
color: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .stat-card.primary {
        --start-color: #4e73df;
--end-color: #224abe;
    }
    
    .stat-card.success {
        --start-color: #1cc88a;
--end-color: #17a673;
    }
    
    .stat-card.warning {
        --start-color: #f6c23e;
--end-color: #dda20a;
    }
    
    .stat-card.danger {
        --start-color: #e74a3b;
--end-color: #c0392b;
    }
    
    .stat-number {
        font-size: 2.5rem;
font-weight: bold;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 0.875rem;
opacity: 0.9;
        margin-top: 0.5rem;
    }
    
    .permission-matrix-preview {
        max-height: 400px;
overflow-y: auto;
        background: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem;
    }
    
    .module-preview {
        background: white;
border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        margin-bottom: 0.75rem;
        overflow: hidden;
}
    
    .module-preview-header {
        background: #4e73df;
color: white;
        padding: 0.75rem 1rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
}
    
    .module-preview-body {
        padding: 0.75rem 1rem;
}
    
    .permission-tag {
        display: inline-block;
background: #e3e6f0;
        color: #5a5c69;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        margin: 0.125rem;
}
    
    .analytics-chart {
        background: white;
border-radius: 0.5rem;
        padding: 1.5rem;
        height: 300px;
    }
    
    .quick-action-grid {
        display: grid;
grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .quick-action-card {
        background: white;
border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        padding: 1.25rem;
        text-align: center;
        transition: all 0.2s ease;
        cursor: pointer;
}
    
    .quick-action-card:hover {
        border-color: #4e73df;
background: #f8f9fc;
    }
    
    .action-icon {
        font-size: 2rem;
margin-bottom: 0.75rem;
        color: #4e73df;
    }
    
    .template-selector {
        background: #f8f9fc;
border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
}
    
    .template-option {
        display: flex;
align-items: center;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.35rem;
        cursor: pointer;
        transition: all 0.2s ease;
}
    
    .template-option:hover {
        border-color: #4e73df;
background: #f8f9fc;
    }
    
    .template-option.selected {
        border-color: #4e73df;
background: #4e73df;
        color: white;
    }
    
    .orphaned-alert {
        background: #fff3cd;
border: 1px solid #ffeaa7;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
}
    
    .comparison-table {
        font-size: 0.875rem;
}
    
    .comparison-table th {
        background: #f8f9fc;
border-top: none;
        font-weight: 600;
        color: #5a5c69;
    }
    
    .permission-status {
        width: 20px;
height: 20px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }
    
    .status-granted { background: #1cc88a;
}
    .status-denied { background: #e74a3b; }
    .status-partial { background: #f6c23e;
}
    
    @media (max-width: 768px) {
        .quick-action-grid {
            grid-template-columns: 1fr;
}
        
        .stat-number {
            font-size: 1.75rem;
}
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-shield-alt mr-2"></i>Permission Management
    </h1>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#analyticsModal">
            <i class="fas fa-chart-bar fa-sm"></i> Analytics
        </button>
        <button class="btn btn-sm btn-warning" onclick="validatePermissions()">
            <i class="fas 
fa-check-circle fa-sm"></i> Validate
        </button>
        <button class="btn btn-sm btn-success" onclick="syncPermissions()">
            <i class="fas fa-sync fa-sm"></i> Sync
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card primary">
            <div class="stat-number">{{ $stats['total_permissions'] }}</div>
            <div class="stat-label">
 
               <i class="fas fa-shield-alt mr-1"></i>Total Permissions
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card success">
            <div class="stat-number">{{ $stats['total_roles'] }}</div>
            <div class="stat-label">
             
   <i class="fas fa-users-cog mr-1"></i>Active Roles
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card warning">
            <div class="stat-number">{{ $stats['unassigned_permissions'] }}</div>
            <div class="stat-label">
                <i class="fas fa-exclamation-triangle mr-1"></i>Unassigned Permissions
     
       </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card danger">
            <div class="stat-number">{{ $stats['system_permissions'] }}</div>
            <div class="stat-label">
                <i class="fas fa-cogs mr-1"></i>System Permissions
            </div>
     
   </div>
    </div>
</div>

@if($stats['unassigned_permissions'] > 0)
<div class="orphaned-alert">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
            <strong>{{ $stats['unassigned_permissions'] }} orphaned permissions found!</strong>
            <p class="mb-0 small">These permissions are not assigned to any role and may need attention.</p>
        </div>
     
   <button class="btn btn-sm btn-warning" onclick="showOrphanedPermissions()">
            <i class="fas fa-search mr-1"></i>Review
        </button>
    </div>
</div>
@endif

<div class="quick-action-grid">
    <div class="quick-action-card" onclick="showBulkCreateModal()">
        <div class="action-icon">
            <i class="fas fa-plus-circle"></i>
        </div>
        <h6 class="font-weight-bold">Bulk Create Permissions</h6>
        <p class="small text-muted mb-0">Create permissions for 
entire modules</p>
    </div>
    
    <div class="quick-action-card" onclick="showTemplateModal()">
        <div class="action-icon">
            <i class="fas fa-magic"></i>
        </div>
        <h6 class="font-weight-bold">Apply Templates</h6>
        <p class="small text-muted mb-0">Apply predefined permission templates to roles</p>
    </div>
    
    <div class="quick-action-card" onclick="showRoleComparisonModal()">
        <div class="action-icon">
     
       <i class="fas fa-balance-scale"></i>
        </div>
        <h6 class="font-weight-bold">Compare Roles</h6>
        <p class="small text-muted mb-0">Compare permissions between different roles</p>
    </div>
    
    <div class="quick-action-card" onclick="showCopyPermissionsModal()">
        <div class="action-icon">
            <i class="fas fa-copy"></i>
        </div>
        <h6 class="font-weight-bold">Copy Permissions</h6>
   
     <p class="small text-muted mb-0">Copy permissions from one role to another</p>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card dashboard-card mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                  
  <i class="fas fa-th-large mr-2"></i>Permission Matrix Overview
                </h6>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit mr-1"></i>Full Editor
                </a>
            </div>
   
         <div class="card-body">
                <div class="permission-matrix-preview">
                    @foreach($groupedPermissions as $module => $permissions)
                        <div class="module-preview">
                        
    <div class="module-preview-header">
                                <span>
                                    <i class="fas fa-{{ getModuleIcon($module) }} mr-2"></i>
                      
              {{ ucfirst($module) }}
                                </span>
                                <span class="badge badge-light">{{ count($permissions) }}</span>
                
            </div>
                            <div class="module-preview-body">
                                @foreach($permissions->take(10) as $permission)
                         
           <span class="permission-tag">
                                        {{ formatPermissionName($permission->name) }}
                                    </span>
          
                      @endforeach
                                @if(count($permissions) > 10)
                                    <span class="permission-tag" style="background: #4e73df;
color: white;">
                                        +{{ count($permissions) - 10 }} more
                                    </span>
                  
              @endif
                            </div>
                        </div>
                    @endforeach
              
  </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <div class="card dashboard-card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
              
      <i class="fas fa-users-cog mr-2"></i>Role Overview
                </h6>
            </div>
            <div class="card-body">
                @foreach($roles as $role)
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
    
                    <div>
                            <h6 class="mb-1">{{ ucfirst($role->name) }}</h6>
                            <small class="text-muted">{{ $role->permissions->count() }} permissions</small>
                 
       </div>
                        <div>
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas 
fa-edit"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            
    
                <div class="text-center mt-3">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i>Manage All Roles
                    </a>
   
             </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
        
            <i class="fas fa-plus-circle mr-2"></i>Bulk Create Permissions
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            
</div>
            <div class="modal-body">
                <form id="bulkCreateForm">
                    <div class="row">
                        <div class="col-md-6">
                        
    <div class="form-group">
                                <label for="module">Module Name</label>
                                <input type="text" id="module" name="module" class="form-control" 
                        
               placeholder="e.g., library, hostel, transport" required>
                            </div>
                        </div>
                        <div class="col-md-6">
    
                        <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" id="description" name="description" class="form-control" 
     
                                  placeholder="Brief module description">
                            </div>
                        </div>
            
        </div>
                    
                    <div class="form-group">
                        <label>Basic Actions</label>
                        <div class="row">
 
                           <div class="col-md-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" 
id="action_view" name="actions[]" value="view" checked>
                                    <label class="form-check-label" for="action_view">View</label>
                                </div>
                           
 </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input 
type="checkbox" class="form-check-input" id="action_create" name="actions[]" value="create">
                                    <label class="form-check-label" for="action_create">Create</label>
                                </div>
                          
  </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    
<input type="checkbox" class="form-check-input" id="action_edit" name="actions[]" value="edit">
                                    <label class="form-check-label" for="action_edit">Edit</label>
                                </div>
                         
   </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                   
 <input type="checkbox" class="form-check-input" id="action_delete" name="actions[]" value="delete">
                                    <label class="form-check-label" for="action_delete">Delete</label>
                                </div>
                        
    </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                  
  <input type="checkbox" class="form-check-input" id="action_manage" name="actions[]" value="manage">
                                    <label class="form-check-label" for="action_manage">Manage</label>
                                </div>
                       
     </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
          
              <label>Additional Permissions</label>
                        <div id="subPermissions">
                            <div class="row sub-permission-row">
                              
  <div class="col-md-6">
                                    <input type="text" name="sub_permissions[0][name]" class="form-control form-control-sm" 
                                           placeholder="Permission name (e.g., 'issue books')">
         
                       </div>
                                <div class="col-md-5">
                                    <input type="text" name="sub_permissions[0][description]" class="form-control form-control-sm" 
   
                                        placeholder="Description">
                                </div>
                            
    <div class="col-md-1">
                                    <button type="button" class="btn btn-sm btn-success" onclick="addSubPermission()">
                                        <i class="fas fa-plus"></i>
            
                        </button>
                                </div>
                            </div>
                
        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
           
     <button type="button" class="btn btn-primary" onclick="createBulkPermissions()">
                    <i class="fas fa-plus mr-1"></i>Create Permissions
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="templateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
  
          <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-magic mr-2"></i>Apply Permission Template
                </h5>
                <button type="button" class="close" data-dismiss="modal">
            
        <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <div class="row">
             
           <div class="col-md-6">
                            <div class="form-group">
                                <label for="template_role">Select Role</label>
                         
       <select id="template_role" name="role_id" class="form-control" required>
                                    <option value="">-- Select Role --</option>
                                    @foreach($roles as $role)
           
                             <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                              
  </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                     
       <div class="form-group">
                                <label for="template_type">Template Type</label>
                                <select id="template_type" name="template" class="form-control" required>
                      
              <option value="">-- Select Template --</option>
                                    <option value="viewer">Viewer (View Only)</option>
                                    <option value="editor">Editor (View + Edit)</option>
   
                                 <option value="manager">Manager (Full CRUD)</option>
                                    <option value="admin">Admin (All Permissions)</option>
                         
       </select>
                            </div>
                        </div>
                    </div>
                    
 
                   <div class="template-selector">
                        <label class="font-weight-bold mb-2">Select Modules</label>
                        <div class="row">
                            
@foreach(['users', 'students', 'faculty', 'courses', 'financials', 'reports', 'settings'] as $module)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                     
                   <input type="checkbox" class="form-check-input" 
                                               id="module_{{ $module }}" name="modules[]" value="{{ $module }}" checked>
                        
                <label class="form-check-label" for="module_{{ $module }}">
                                            <i class="fas fa-{{ getModuleIcon($module) }} mr-2"></i>
                               
             {{ ucfirst($module) }}
                                        </label>
                                    </div>
         
                       </div>
                            @endforeach
                        </div>
                    </div>
     
           </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="applyTemplate()">
                    <i class="fas fa-magic mr-1"></i>Apply 
Template
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="roleComparisonModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
     
               <i class="fas fa-balance-scale mr-2"></i>Role Comparison
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
          
  </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="compare_role_1">First Role</label>
                    
    <select id="compare_role_1" class="form-control">
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                
<option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
  
                      <label for="compare_role_2">Second Role</label>
                        <select id="compare_role_2" class="form-control">
                            <option value="">-- Select Role --</option>
                  
          @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                       
 </select>
                    </div>
                </div>
                
                <div id="comparisonResults" style="display: none;">
                    <div class="table-responsive">
       
                 <table class="table table-sm comparison-table">
                            <thead>
                                <tr>
                    
                <th>Permission</th>
                                    <th id="role1Header">Role 1</th>
                                    <th id="role2Header">Role 2</th>
        
                            <th>Status</th>
                                </tr>
                            </thead>
            
                <tbody id="comparisonTableBody">
                            </tbody>
                        </table>
                    </div>
           
     </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="compareRoles()">
                    <i class="fas fa-search mr-1"></i>Compare
       
         </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="copyPermissionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
             
       <i class="fas fa-copy mr-2"></i>Copy Permissions
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
      
      <div class="modal-body">
                <form id="copyPermissionsForm">
                    <div class="form-group">
                        <label for="source_role">Source Role (copy from)</label>
                        <select id="source_role" name="source_role_id" 
class="form-control" required>
                            <option value="">-- Select Source Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ 
ucfirst($role->name) }} ({{ $role->permissions->count() }} permissions)</option>
                            @endforeach
                        </select>
                    </div>
                    
   
                 <div class="form-group">
                        <label for="target_role">Target Role (copy to)</label>
                        <select id="target_role" name="target_role_id" class="form-control" required>
                          
  <option value="">-- Select Target Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ ucfirst($role->name) }} ({{ $role->permissions->count() }} permissions)</option>
                      
      @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
         
               <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="merge_permissions" name="merge">
                            <label class="form-check-label" for="merge_permissions">
                      
          Merge permissions (add to existing)
                            </label>
                        </div>
                        <small class="form-text text-muted">
        
                    If unchecked, target role permissions will be replaced entirely
                        </small>
                    </div>
                </form>
            
</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="copyPermissions()">
                    <i class="fas fa-copy mr-1"></i>Copy Permissions
                </button>
       
     </div>
        </div>
    </div>
</div>

<div class="modal fade" id="analyticsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar mr-2"></i>Permission Analytics
     
           </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
         
       <div id="analyticsContent">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading analytics...</p>
               
     </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize components
    initializeDashboard();
});

function initializeDashboard() {
    // Load initial data
    loadPermissionStats();
}

// Modal Functions
function showBulkCreateModal() {
    $('#bulkCreateModal').modal('show');
}

function showTemplateModal() {
    $('#templateModal').modal('show');
}

function showRoleComparisonModal() {
    $('#roleComparisonModal').modal('show');
}

function showCopyPermissionsModal() {
    $('#copyPermissionsModal').modal('show');
}

// Bulk Create Functions
function addSubPermission() {
    const container = $('#subPermissions');
    const index = container.find('.sub-permission-row').length;
const newRow = `
        <div class="row sub-permission-row mt-2">
            <div class="col-md-6">
                <input type="text" name="sub_permissions[${index}][name]" class="form-control form-control-sm" 
                       placeholder="Permission name">
            </div>
            <div class="col-md-5">
   
             <input type="text" name="sub_permissions[${index}][description]" class="form-control form-control-sm" 
                       placeholder="Description">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSubPermission(this)">
             
       <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
container.append(newRow);
}

function removeSubPermission(button) {
    $(button).closest('.sub-permission-row').remove();
}

function createBulkPermissions() {
    const formData = new FormData($('#bulkCreateForm')[0]);
// Convert FormData to regular object for JSON
    const data = {};
for (let [key, value] of formData.entries()) {
        if (key.includes('[')) {
            // Handle array inputs
            const matches = key.match(/(\w+)\[(\d+)\]\[(\w+)\]/);
if (matches) {
                const [, arrayName, index, field] = matches;
if (!data[arrayName]) data[arrayName] = [];
                if (!data[arrayName][index]) data[arrayName][index] = {};
                data[arrayName][index][field] = value;
}
        } else if (key.endsWith('[]')) {
            // Handle checkbox arrays
            const arrayName = key.slice(0, -2);
if (!data[arrayName]) data[arrayName] = [];
            data[arrayName].push(value);
        } else {
            data[key] = value;
}
    }
    
    $.ajax({
        url: '{{ route("admin.permissions.bulk-create") }}',
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
     
           showAlert('success', response.message);
                $('#bulkCreateModal').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', response.message);
            }
        },
  
      error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response.message || 'Failed to create permissions');
        }
    });
}

// Template Functions
function applyTemplate() {
    const formData = {
        role_id: $('#template_role').val(),
        template: $('#template_type').val(),
        modules: $('input[name="modules[]"]:checked').map(function() {
            return $(this).val();
        }).get()
    };
if (!formData.role_id || !formData.template) {
        showAlert('error', 'Please select both role and template type');
return;
    }
    
    $.ajax({
        url: '{{ route("admin.permissions.apply-template") }}',
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
        
        showAlert('success', response.message);
                $('#templateModal').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', response.message);
            }
        },
     
   error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response.message || 'Failed to apply template');
        }
    });
}

// Role Comparison Functions
function compareRoles() {
    const role1Id = $('#compare_role_1').val();
    const role2Id = $('#compare_role_2').val();
if (!role1Id || !role2Id) {
        showAlert('error', 'Please select both roles to compare');
        return;
}
    
    if (role1Id === role2Id) {
        showAlert('error', 'Please select different roles to compare');
return;
    }
    
    // Get role permissions and compare
    Promise.all([
        $.get(`{{ url('admin/roles') }}/${role1Id}/permissions`),
        $.get(`{{ url('admin/roles') }}/${role2Id}/permissions`)
    ]).then(([role1Data, role2Data]) => {
        displayComparison(role1Data, role2Data);
    }).catch(error => {
        showAlert('error', 'Failed to fetch role data for comparison');
    });
}

function displayComparison(role1Data, role2Data) {
    $('#role1Header').text(role1Data.role);
    $('#role2Header').text(role2Data.role);
    
    // Get all unique permissions
    const allPermissions = [...new Set([
        ...role1Data.permissions,
        ...role2Data.permissions
    ])].sort();
const tbody = $('#comparisonTableBody');
    tbody.empty();
    
    allPermissions.forEach(permission => {
        const hasRole1 = role1Data.permissions.includes(permission);
        const hasRole2 = role2Data.permissions.includes(permission);
        
        let status = '';
        let statusClass = '';
        
        if (hasRole1 && hasRole2) {
            status = 'Both';
       
     statusClass = 'status-granted';
        } else if (hasRole1) {
            status = 'Role 1 Only';
            statusClass = 'status-partial';
        } else if (hasRole2) {
            status = 'Role 2 Only';
            statusClass = 'status-partial';
        }
 
       
        tbody.append(`
            <tr>
                <td>${permission}</td>
                <td><span class="permission-status ${hasRole1 ? 'status-granted' : 'status-denied'}"></span>${hasRole1 ? 'Yes' : 'No'}</td>
                <td><span class="permission-status ${hasRole2 ? 'status-granted' : 'status-denied'}"></span>${hasRole2 ? 'Yes' : 'No'}</td>
     
           <td><span class="permission-status ${statusClass}"></span>${status}</td>
            </tr>
        `);
});
    
    $('#comparisonResults').show();
}

// Copy Permissions Functions
function copyPermissions() {
    const formData = {
        source_role_id: $('#source_role').val(),
        target_role_id: $('#target_role').val(),
        merge: $('#merge_permissions').is(':checked')
    };
if (!formData.source_role_id || !formData.target_role_id) {
        showAlert('error', 'Please select both source and target roles');
return;
    }
    
    if (formData.source_role_id === formData.target_role_id) {
        showAlert('error', 'Source and target roles cannot be the same');
return;
    }
    
    $.ajax({
        url: '{{ route("admin.permissions.copy-role-permissions") }}',
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
        
        showAlert('success', response.message);
                $('#copyPermissionsModal').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response.message || 'Failed to copy permissions');
        }
    });
}

// Utility Functions
function showOrphanedPermissions() {
    $.get('{{ route("admin.permissions.orphaned") }}')
        .then(response => {
            if (response.success) {
                let message = `Found ${response.orphaned_count} orphaned permissions:\n\n`;
                response.orphaned_permissions.forEach(permission => {
                    message += `• ${permission}\n`;
   
             });
                message += '\nWould you like to clean them up?';
                
                if (confirm(message)) {
                    cleanupOrphanedPermissions(response.orphaned_permissions);
         
       }
            }
        })
        .catch(error => {
            showAlert('error', 'Failed to fetch orphaned permissions');
        });
}

function cleanupOrphanedPermissions(permissions) {
    $.ajax({
        url: '{{ route("admin.permissions.cleanup-orphaned") }}',
        method: 'POST',
        data: { permissions: permissions },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
        
        showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
           
 const response = xhr.responseJSON;
            showAlert('error', response.message || 'Failed to cleanup orphaned permissions');
        }
    });
}

function validatePermissions() {
    $.get('{{ route("admin.permissions.validate-structure") }}')
        .then(response => {
            if (response.success) {
                if (response.issues_found === 0) {
                    showAlert('success', 'Permission structure validation passed! No issues found.');
                } else {
   
                 let message = `Found ${response.issues_found} issues:\n\n`;
                    response.issues.forEach(issue => {
                        message += `• ${issue.issue}\n`;
                    });
         
           showAlert('warning', `Validation completed with ${response.issues_found} issues. Check console for details.`);
                    console.log('Permission validation issues:', response.issues);
                }
            }
        })
        .catch(error => {
           
 showAlert('error', 'Failed to validate permission structure');
        });
}

function syncPermissions() {
    if (!confirm('This will sync permissions with the system. Continue?')) {
        return;
}
    
    $.ajax({
        url: '{{ route("admin.permissions.sync") }}',
        method: 'POST',
        data: {
            create_missing: true,
            remove_unused: false // Be careful with this
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  
      },
        success: function(response) {
            if (response.success) {
                showAlert('success', `${response.message} Created: ${response.results.created}, Removed: ${response.results.removed}`);
                setTimeout(() => location.reload(), 2000);
            } else {
                
showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response.message || 'Failed to sync permissions');
        }
    });
}

function loadPermissionStats() {
    // This could load real-time statistics
    // For now, we'll use the server-side data
}

// Analytics Modal
$('#analyticsModal').on('show.bs.modal', function() {
    loadAnalytics();
});
function loadAnalytics() {
    $('#analyticsContent').html(`
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading analytics...</p>
        </div>
    `);
$.get('{{ route("admin.permissions.analytics") }}')
        .then(response => {
            if (response.success) {
                displayAnalytics(response.analytics);
            } else {
                $('#analyticsContent').html(`
                    <div class="alert alert-danger">
      
                  <i class="fas fa-exclamation-triangle mr-2"></i>
                        Failed to load analytics data.
                    </div>
                `);
            }
   
     })
        .catch(error => {
            $('#analyticsContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Error loading analytics: ${error.message || 'Unknown error'}
      
          </div>
            `);
        });
}

function displayAnalytics(analytics) {
    const html = `
        <div class="row">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
              
          <h4 class="text-primary">${analytics.overview.total_permissions}</h4>
                        <small>Total Permissions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
   
             <div class="card border-success">
                    <div class="card-body text-center">
                        <h4 class="text-success">${analytics.overview.total_roles}</h4>
                        <small>Total Roles</small>
             
       </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
            
            <h4 class="text-warning">${analytics.overview.orphaned_permissions}</h4>
                        <small>Orphaned Permissions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
 
               <div class="card border-info">
                    <div class="card-body text-center">
                        <h4 class="text-info">${analytics.overview.unused_roles}</h4>
                        <small>Unused Roles</small>
           
         </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
         
       <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Permission Distribution by Module</h6>
                    </div>
                    <div class="card-body">
  
                      <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
               
                     <tr>
                                        <th>Module</th>
                                       
 <th>Count</th>
                                        <th>Assigned</th>
                                        <th>%</th>
                   
                 </tr>
                                </thead>
                                <tbody>
                   
                 ${Object.entries(analytics.permission_distribution).map(([module, data]) => `
                                        <tr>
                                        
    <td>${module}</td>
                                            <td>${data.count}</td>
                                            <td>${data.assigned}</td>
        
                                    <td>${data.percentage}%</td>
                                        </tr>
                        
            `).join('')}
                                </tbody>
                            </table>
                        </div>
    
                </div>
                </div>
            </div>
            
            <div class="col-md-6">
                
<div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Role Complexity</h6>
                    </div>
                    <div class="card-body">
           
             <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                        
            <tr>
                                        <th>Role</th>
                                        <th>Permissions</th>
        
                                <th>Users</th>
                                    </tr>
                                
</thead>
                                <tbody>
                                    ${analytics.role_complexity.map(role => `
                              
          <tr>
                                            <td>${role.role}</td>
                                            <td>${role.permissions_count}</td>
  
                                          <td>${role.users_count}</td>
                                        </tr>
                  
                  `).join('')}
                                </tbody>
                            </table>
                      
  </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
               <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Most Assigned Permissions</h6>
                    </div>
           
         <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
   
                                 <tr>
                                        <th>Permission</th>
                           
             <th>Roles</th>
                                        <th>Users</th>
                                    </tr>
           
                     </thead>
                                <tbody>
                                    ${analytics.top_permissions.map(perm => `
         
                               <tr>
                                            <td><small>${perm.permission}</small></td>
                         
                   <td>${perm.roles_count}</td>
                                            <td>${perm.users_count}</td>
                                     
   </tr>
                                    `).join('')}
                                </tbody>
                            </table>
 
                       </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Permission Usage by Action Type</h6>
                    
</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                        
        <thead>
                                    <tr>
                                        <th>Action</th>
                
                        <th>Total</th>
                                        <th>Assigned</th>
                                    
    <th>Usage %</th>
                                    </tr>
                                </thead>
                           
     <tbody>
                                    ${Object.entries(analytics.permission_usage).map(([action, data]) => `
                                        <tr>
                
                            <td><span class="badge badge-${getActionBadgeColor(action)}">${action}</span></td>
                                            <td>${data.count}</td>
                          
                  <td>${data.assigned}</td>
                                            <td>${data.count > 0 ?
Math.round((data.assigned / data.count) * 100) : 0}%</td>
                                        </tr>
                                    `).join('')}
                  
              </tbody>
                            </table>
                        </div>
                    </div>
              
  </div>
            </div>
        </div>
    `;
$('#analyticsContent').html(html);
}

function getActionBadgeColor(action) {
    const colors = {
        'view': 'info',
        'create': 'success', 
        'edit': 'warning',
        'delete': 'danger',
        'manage': 'primary'
    };
return colors[action] || 'secondary';
}

// Show Alert Function (you may need to define this based on your notification system)
function showAlert(type, message) {
    // Example implementation - adjust based on your notification system
    const alertClass = type === 'success' ?
'alert-success' : 
                      type === 'error' ?
'alert-danger' : 
                      type === 'warning' ?
'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
// Append to a container or use your existing notification system
    $('.container-fluid').first().prepend(alertHtml);
// Auto-remove after 5 seconds
    setTimeout(() => {
        $('.alert').first().fadeOut();
    }, 5000);
}

// Auto-refresh stats every 30 seconds
setInterval(loadPermissionStats, 30000);
</script>
@endpush