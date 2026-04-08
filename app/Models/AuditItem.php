<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditItem extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['audit_id', 'asset_id', 'status', 'notes'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
