@extends('layouts.theme')
@section('title', 'Dashboard')

@push('styles')
<style>
    .grid-stack { background: #f8f9fc; }
    .grid-stack-item-content { background-color: transparent; border: none; box-shadow: none !important; overflow: visible; }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
</div>

{{-- This is the grid that will be populated by GridStack --}}
<div class="grid-stack">
    @foreach ($layout as $widget)
        <div class="grid-stack-item" gs-id="{{ $widget->id }}" gs-x="{{ $widget->pivot->col }}" gs-y="{{ $widget->pivot->row }}" gs-w="{{ $widget->pivot->width }}" gs-h="{{ $widget->pivot->height }}">
            <div class="grid-stack-item-content">
                {{-- This dynamically includes the correct widget view based on its saved view_path --}}
                @include($widget->view_path, ['widgetData' => $widgetData])
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize GridStack in static (read-only) mode
    let grid = GridStack.init({
        staticGrid: true, // This makes the dashboard non-editable for the user
        float: true,
        cellHeight: '80px',
    });
});
</script>
@endpush
