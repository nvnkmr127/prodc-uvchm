<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'secret_token',
        'mapping_rules',
        'last_payload',
        'is_active',
        'source_name',
        'auto_followup_days',
        'description',
        'created_by',
        'last_called_at',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'mapping_rules' => 'array',
        'last_payload' => 'array',
        'is_active' => 'boolean',
        'last_called_at' => 'datetime',
    ];

    /**
     * Get the user who created the webhook.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the full Webhook URL.
     */
    public function getUrlAttribute(): string
    {
        return url("/api/v1/webhooks/{$this->slug}");
    }
}
