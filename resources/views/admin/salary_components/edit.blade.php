@extends('layouts.theme')
@section('title', 'Edit Salary Component')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Salary Component</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.salary-components.update', $salaryComponent) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group mb-3">
                <label for="name">Component Name</label>
                <input type="text" name="name" class="form-control" value="{{ $salaryComponent->name }}" required>
            </div>
            <div class="form-group mb-3">
                <label for="type">Component Type</label>
                <select name="type" class="form-control" required>
                    <option value="Earning" @if($salaryComponent->type == 'Earning') selected @endif>Earning</option>
                    <option value="Deduction" @if($salaryComponent->type == 'Deduction') selected @endif>Deduction</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="calculation_type">Calculation Type</label>
                <select name="calculation_type" class="form-control" required>
                    <option value="fixed" @if($salaryComponent->calculation_type == 'fixed') selected @endif>Fixed Amount</option>
                    <option value="percentage" @if($salaryComponent->calculation_type == 'percentage') selected @endif>Percentage</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="base_component_id">Base Component (If Percentage)</label>
                <select name="base_component_id" class="form-control">
                    <option value="">-- None --</option>
                    @foreach($components as $component)
                        <option value="{{ $component->id }}" @if($salaryComponent->base_component_id == $component->id) selected @endif>{{ $component->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Select the component this is a percentage of (e.g., HRA is a percentage of Basic Pay).</small>
            </div>

            <div class="form-group mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_taxable" value="1" id="is_taxable" @if($salaryComponent->is_taxable) checked @endif>
                    <label class="form-check-label" for="is_taxable">
                        Is Taxable
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="is_mandatory" @if($salaryComponent->is_mandatory) checked @endif>
                    <label class="form-check-label" for="is_mandatory">
                        Is Mandatory
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Update Component</button>
        </form>
    </div>
</div>
@endsection