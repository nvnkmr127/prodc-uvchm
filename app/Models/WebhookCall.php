<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class WebhookCall extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'success',
        'status_code',
        'payload',
        'response_body',
        'execution_time_ms',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}