@extends('layouts.theme')
@section('title', 'Manage Assets')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Assets</h1>
    <div>
        <button class="btn btn-sm btn-danger shadow-sm" id="bulk-delete-btn" style="display: none;"><i class="fas fa-trash fa-sm text-white-50"></i> Delete Selected</button>
        <button class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addAssetModal"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Asset</button>
    <a href="#" class="btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#importAssetsModal">
            <i class="fas fa-file-import fa-sm text-white-50"></i> Bulk Import Assets
        </a> </div>

<div class="modal fade" id="importAssetsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Import Assets</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form action="{{ route('admin.assets.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <p>Upload an Excel or CSV file. The columns must match the required format.</p>
                        <a href="{{ route('admin.assets.import.sample') }}" class="btn btn-sm btn-link p-0">
                            Download Sample Template
                        </a>
                        <p class="mt-2 mb-0">
                            Required columns: <code>name</code>, <code>asset_category_id</code>, <code>purchase_date</code>, <code>cost</code>, <code>serial_number</code>
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="file">Select File (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" class="form-control-file" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import Assets</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="assetsDataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 5%;"><input type="checkbox" id="select-all"></th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $asset)
                        <tr data-asset-id="{{ $asset->id }}">
                            <td><input type="checkbox" class="asset-checkbox" value="{{ $asset->id }}"></td>
                            <td>{{ $asset->name }} <br><small class="text-muted">{{ $asset->asset_code }}</small></td>
                            <td>{{ $asset->category?->name ?? 'N/A' }}</td>
                            <td>{{ $asset->location }}</td>
                            <td>{{ $asset->condition }}</td>
                            <td>
                                <button class="btn btn-info btn-sm view-btn" title="Quick View"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-warning btn-sm edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add New Asset</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <form action="{{ route('admin.assets.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-3"><label>Asset Name*</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Asset Code</label><input type="text" name="asset_code" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label>Category*</label><select name="asset_category_id" class="form-control" required><option value="">-- Select --</option>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
                    <div class="col-md-6 mb-3"><label>Location*</label><input type="text" name="location" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label>Condition*</label><select name="condition" class="form-control" required><option>Good</option><option>Fair</option><option>Needs Repair</option><option>Damaged</option><option>Missing</option></select></div>
                    <div class="col-md-6 mb-3"><label>Quantity*</label><input type="number" name="quantity" class="form-control" value="1" required></div>
                    <div class="col-md-6 mb-3"><label>Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" class="form-control"></div>
                </div>
                <button type="submit" class="btn btn-primary">Save Asset</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Edit Asset Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Asset</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <form id="editAssetForm" method="POST">
                @csrf
                @method('PATCH')
                {{-- Form fields are identical to the 'Add' modal, but with IDs for population --}}
                <div class="row">
                     <div class="col-md-8 mb-3"><label>Asset Name*</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Asset Code</label><input type="text" name="asset_code" id="edit_asset_code" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label>Category*</label><select name="asset_category_id" id="edit_asset_category_id" class="form-control" required>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
                    <div class="col-md-6 mb-3"><label>Location*</label><input type="text" name="location" id="edit_location" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label>Condition*</label><select name="condition" id="edit_condition" class="form-control" required><option>Good</option><option>Fair</option><option>Needs Repair</option><option>Damaged</option><option>Missing</option></select></div>
                    <div class="col-md-6 mb-3"><label>Quantity*</label><input type="number" name="quantity" id="edit_quantity" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label>Purchase Date</label><input type="date" name="purchase_date" id="edit_purchase_date" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" id="edit_purchase_price" class="form-control"></div>
                </div>
                <button type="submit" class="btn btn-primary">Update Asset</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="viewAssetModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="view_name">Asset Details</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <p><strong>Asset Code:</strong> <span id="view_asset_code"></span></p>
            <p><strong>Category:</strong> <span id="view_category"></span></p>
            <p><strong>Location:</strong> <span id="view_location"></span></p>
            <p><strong>Condition:</strong> <span id="view_condition"></span></p>
            <p><strong>Quantity:</strong> <span id="view_quantity"></span></p>
            <p><strong>Purchase Date:</strong> <span id="view_purchase_date"></span></p>
            <p><strong>Purchase Price:</strong> <span id="view_purchase_price"></span></p>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#assetsDataTable').DataTable({"order": [[ 1, "asc" ]]});
    var allAssets = @json($assets->keyBy('id'));

    // Handle Edit button click
    table.on('click', '.edit-btn', function() {
        var assetId = $(this).closest('tr').data('asset-id');
        var asset = allAssets[assetId];
        
        var url = "{{ route('admin.assets.update', ':id') }}".replace(':id', assetId);
        $('#editAssetForm').attr('action', url);
        
        $('#edit_name').val(asset.name);
        $('#edit_asset_code').val(asset.asset_code);
        $('#edit_asset_category_id').val(asset.asset_category_id);
        $('#edit_location').val(asset.location);
        $('#edit_condition').val(asset.condition);
        $('#edit_quantity').val(asset.quantity);
        $('#edit_purchase_date').val(asset.purchase_date);
        $('#edit_purchase_price').val(asset.purchase_price);
        
        $('#editAssetModal').modal('show');
    });

    // Handle View button click
    table.on('click', '.view-btn', function() {
        var assetId = $(this).closest('tr').data('asset-id');
        var asset = allAssets[assetId];
        
        $('#view_name').text(asset.name);
        $('#view_asset_code').text(asset.asset_code || 'N/A');
        $('#view_category').text(asset.category ? asset.category.name : 'N/A');
        $('#view_location').text(asset.location);
        $('#view_condition').text(asset.condition);
        $('#view_quantity').text(asset.quantity);
        $('#view_purchase_date').text(asset.purchase_date || 'N/A');
        $('#view_purchase_price').text(asset.purchase_price ? `{{ setting("currency_symbol", "₹") }} ${asset.purchase_price}` : 'N/A');
        
        $('#viewAssetModal').modal('show');
    });

    // Handle Bulk Select
    $('#select-all').on('click', function() {
        $('.asset-checkbox').prop('checked', this.checked);
        toggleBulkDeleteButton();
    });
    table.on('click', '.asset-checkbox', function() {
        toggleBulkDeleteButton();
    });

    function toggleBulkDeleteButton() {
        var checkedCount = $('.asset-checkbox:checked').length;
        $('#bulk-delete-btn').toggle(checkedCount > 0);
    }
    
    // Handle Bulk Delete button click
    $('#bulk-delete-btn').on('click', function() {
        var selectedIds = $('.asset-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length > 0 && confirm('Are you sure you want to delete the selected assets?')) {
            var form = $('<form>', {
                'method': 'POST',
                'action': '{{ route("admin.assets.bulkDestroy") }}'
            }).append(
                $('<input>', {'name': '_token', 'value': '{{ csrf_token() }}', 'type': 'hidden'}),
                $('<input>', {'name': '_method', 'value': 'DELETE', 'type': 'hidden'})
            );
            selectedIds.forEach(function(id) {
                form.append($('<input>', {'name': 'ids[]', 'value': id, 'type': 'hidden'}));
            });
            form.appendTo('body').submit();
        }
    });
});
</script>
@endpush
