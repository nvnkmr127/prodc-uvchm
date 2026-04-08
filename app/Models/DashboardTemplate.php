<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardTemplate extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = [
        'name', 'description', 'category', 'layout', 'config',
        'is_public', 'created_by', 'usage_count', 'preview_image',
    ];

    protected $casts = [
        'layout' => 'array',
        'config' => 'array',
        'is_public' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
