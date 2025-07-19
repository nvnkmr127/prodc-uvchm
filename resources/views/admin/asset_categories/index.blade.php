@extends('layouts.theme')
@section('title', 'Manage Asset Categories')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Asset Categories</h1>
    {{-- This button now opens the Add modal --}}
    <button class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addCategoryModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Category
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Asset Categories</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th>Category Name</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td>
                                {{-- The Edit button now triggers the edit modal --}}
                                <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $category->id }}" data-name="{{ $category->name }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.asset-categories.destroy', $category) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add New Asset Category</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <form action="{{ route('admin.asset-categories.store') }}" method="POST">
                    @csrf
                    <div class="form-group"><label for="name">Category Name</label><input type="text" name="name" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary mt-3">Save Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Asset Category</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <form id="editCategoryForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-group"><label for="edit_name">Category Name</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary mt-3">Update Category</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize the interactive DataTable
    $('#dataTable').DataTable({
        "order": [[ 0, "desc" ]] // Sort by ID descending by default
    });

    // Handle the Edit button click to populate and show the modal
    $('#dataTable').on('click', '.edit-btn', function() {
        var categoryId = $(this).data('id');
        var categoryName = $(this).data('name');
        
        // Set the form action dynamically
        var url = "{{ route('admin.asset-categories.update', ':id') }}";
        url = url.replace(':id', categoryId);
        $('#editCategoryForm').attr('action', url);
        
        // Populate the input field
        $('#edit_name').val(categoryName);
        
        // Show the modal
        $('#editCategoryModal').modal('show');
    });
});
</script>
@endpush
