@extends('layouts.theme')
@section('title', 'Financial Hub')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Financial Hub</h1>
    <a href="{{ route('admin.invoices.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Generate Batch Invoices</a>
</div>

{{-- Live Search Form --}}
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Find Student Ledger</h6></div>
    <div class="card-body">
        <div class="form-group">
            <label for="student-search">Search by Student Name or Enrollment Number</label>
            <input type="text" id="student-search" class="form-control" placeholder="Start typing to search...">
        </div>
    </div>
</div>

{{-- Search Results Container --}}
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Search Results</h6></div>
    <div class="card-body" id="search-results-container">
        <div class="text-center text-muted">
            <i class="fas fa-search fa-2x mb-2"></i>
            <p>Please use the search bar above to find a student's ledger.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('student-search');
    const resultsContainer = document.getElementById('search-results-container');
    let debounceTimer;

    searchInput.addEventListener('keyup', function () {
        clearTimeout(debounceTimer);
        const query = this.value;

        // Use a debounce timer to prevent sending too many requests while typing
        debounceTimer = setTimeout(() => {
            if (query.length < 2) {
                resultsContainer.innerHTML = `<div class="text-center text-muted"><i class="fas fa-search fa-2x mb-2"></i><p>Please use the search bar above to find a student's ledger.</p></div>`;
                return;
            }

            // Show a loading indicator
            resultsContainer.innerHTML = '<div class="col-12 text-center my-5"><i class="fas fa-spinner fa-spin fa-3x"></i></div>';
            
            fetch(`{{ route('admin.financials.index') }}?search=${query}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                resultsContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching search results:', error);
                resultsContainer.innerHTML = '<div class="col-12 text-center my-5"><p class="text-danger">Failed to load results.</p></div>';
            });
        }, 300); // 300ms delay
    });
});
</script>
@endpush
