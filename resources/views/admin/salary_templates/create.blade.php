@extends('layouts.theme')
@section('title', 'Add Salary Template')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Salary Template</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.salary-templates.store') }}" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label for="name">Template Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Senior Faculty Scale" required>
            </div>
            <div class="form-group mb-3">
                <label for="description">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <hr>
            <h5 class="mb-3">Template Components</h5>
            <div id="components-container">
                <div class="row component-row mb-2">
                    <div class="col-md-5">
                        <select name="components[0][salary_component_id]" class="form-control" required>
                            <option value="">-- Select Component --</option>
                            @foreach($components as $comp)
                                <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->calculation_type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="number" step="0.01" min="0" name="components[0][value]" class="form-control" placeholder="Value (Amount or Percentage)" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-component"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>
            
            <button type="button" id="add-component" class="btn btn-secondary btn-sm mb-3"><i class="fas fa-plus"></i> Add Component</button>

            <div>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let compIndex = 1;
        const container = document.getElementById('components-container');
        
        document.getElementById('add-component').addEventListener('click', function() {
            const html = `
                <div class="row component-row mb-2">
                    <div class="col-md-5">
                        <select name="components[${compIndex}][salary_component_id]" class="form-control" required>
                            <option value="">-- Select Component --</option>
                            @foreach($components as $comp)
                                <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->calculation_type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="number" step="0.01" min="0" name="components[${compIndex}][value]" class="form-control" placeholder="Value (Amount or Percentage)" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-component"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            compIndex++;
        });

        container.addEventListener('click', function(e) {
            if (e.target.closest('.remove-component')) {
                e.target.closest('.component-row').remove();
            }
        });
    });
</script>
@endpush
@endsection
