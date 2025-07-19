@extends('layouts.theme')
@section('title', 'Create Fee Structure')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Create New Fee Structure</h1>
    <a href="{{ route('admin.fee-structures.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
    </a>
</div>

{{-- Display validation errors if any --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.fee-structures.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-12">
            {{-- Card for selecting the Batch and Payment Terms --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Core Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="course_select">1. Select a Course</label>
                                <select id="course_select" class="form-control" required>
                                    <option value="">-- Select a Course First --</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="batch_id">2. Select a Batch*</label>
                                <select name="batch_id" id="batch_id" class="form-control" required disabled>
                                    <option value="">-- Select a Course First --</option>
                                </select>
                                @error('batch_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        {{-- REVISED: Added Payment Terms field --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payment_terms">3. Number of Payment Terms*</label>
                                <input type="number" name="payment_terms" class="form-control" value="{{ old('payment_terms', 1) }}" min="1" max="12" required>
                                <small class="form-text text-muted">How many installments for this fee?</small>
                                @error('payment_terms')
                                     <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card for adding fee components --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">4. Add Fee Components</h6>
                    <button type="button" class="btn btn-sm btn-success" id="add-component-btn">
                        <i class="fas fa-plus fa-sm"></i> Add Component
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted">Add one or more fee components that make up the total fee for the selected batch.</p>
                    <div id="components-wrapper">
                        {{-- Dynamic component rows will be inserted here by JavaScript --}}
                    </div>
                    <hr>
                    <div class="text-right">
                        <h5 class="font-weight-bold">Total: <span id="calculated-total" class="text-success">₹0.00</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 text-right mb-4">
             <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save fa-sm"></i> Save Fee Structure</button>
        </div>
    </div>
</form>

<!-- Hidden template for new component rows -->
<div id="component-template" style="display: none;">
    <div class="row component-row align-items-center mb-2">
        <div class="col-md-7">
            <select name="components[__INDEX__][fee_category_id]" class="form-control component-category" required>
                <option value="">-- Select a Category --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" step="0.01" min="0" name="components[__INDEX__][amount]" class="form-control component-amount" placeholder="Amount (e.g., 65000)" required>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger remove-component-btn" title="Remove Component"><i class="fas fa-times"></i></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// This script remains the same as it correctly handles fetching batches and calculating totals.
document.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.getElementById('course_select');
    const batchSelect = document.getElementById('batch_id');
    const wrapper = document.getElementById('components-wrapper');
    const template = document.getElementById('component-template').innerHTML;
    const calculatedTotalEl = document.getElementById('calculated-total');
    let componentIndex = 0;

    courseSelect.addEventListener('change', function() {
        const courseId = this.value;
        batchSelect.innerHTML = '<option value="">Loading...</option>';
        batchSelect.disabled = true;

        if (!courseId) {
            batchSelect.innerHTML = '<option value="">-- Select a Course First --</option>';
            return;
        }

        const url = `/admin/get-batches-for-course/${courseId}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                batchSelect.innerHTML = '<option value="">-- Select a Batch --</option>';
                if (data.length > 0) {
                    data.forEach(batch => {
                        if (!batch.has_fee_structure) {
                           batchSelect.innerHTML += `<option value="${batch.id}">${batch.name}</option>`;
                        }
                    });
                    batchSelect.disabled = false;
                } else {
                    batchSelect.innerHTML = '<option value="">-- No Batches Found --</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching batches:', error);
                batchSelect.innerHTML = '<option value="">-- Error Loading Batches --</option>';
            });
    });

    function updateCalculations() {
        let total = 0;
        document.querySelectorAll('.component-row .component-amount').forEach(function (input) {
            total += parseFloat(input.value) || 0;
        });
        calculatedTotalEl.textContent = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.getElementById('add-component-btn').addEventListener('click', function () {
        const newRowHtml = template.replace(/__INDEX__/g, componentIndex);
        const newRow = document.createElement('div');
        newRow.innerHTML = newRowHtml;
        wrapper.appendChild(newRow.firstElementChild);
        componentIndex++;
        updateCalculations();
    });

    wrapper.addEventListener('click', function (e) {
        if (e.target.closest('.remove-component-btn')) {
            e.target.closest('.component-row').remove();
            updateCalculations();
        }
    });

    wrapper.addEventListener('input', function(e) {
        if (e.target.classList.contains('component-amount')) {
            updateCalculations();
        }
    });
    
    if (wrapper.children.length === 0) {
        document.getElementById('add-component-btn').click();
    }
});
</script>
@endpush