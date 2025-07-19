<div class="widget-header">
    {{ $widget->name }}
    <button class="btn btn-sm btn-danger position-absolute" style="top: 2px; right: 2px;" onclick="const el = this.closest('.grid-stack-item'); window.grid.removeWidget(el);">&times;</button>
</div>
<div class="widget-body p-2 text-center text-muted">
    <small>Live preview will appear on the actual user dashboard.</small>
</div>