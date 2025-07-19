<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\WebhookEnabled;

class WidgetCategory extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color', 'order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class, 'category', 'slug');
    }
}