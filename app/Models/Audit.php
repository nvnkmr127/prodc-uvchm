<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['audit_date', 'audited_by_user_id', 'status', 'notes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }
}
