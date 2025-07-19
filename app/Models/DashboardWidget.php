<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class DashboardWidget extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'dashboard_id', 'widget_id', 'instance_id', 'grid_x', 'grid_y',
        'grid_w', 'grid_h', 'config', 'order', 'is_visible'
    ];

    protected $casts = [
        'config' => 'array',
        'is_visible' => 'boolean'
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }
}