<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use App\Traits\WebhookEnabled;

class Dashboard extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'name', 'role_id', 'config', 'theme', 'is_default', 'last_accessed_at'
    ];

    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
        'last_accessed_at' => 'datetime'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('order');
    }
}