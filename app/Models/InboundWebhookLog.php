<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_webhook_id',
        'payload',
        'status_code',
        'error_message',
        'ip_address',
        'method',
        'enquiry_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function inboundWebhook(): BelongsTo
    {
        return $this->belongsTo(InboundWebhook::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
