<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class AuditItem extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = ['audit_id', 'asset_id', 'status', 'notes'];

    public function audit(): BelongsTo { return $this->belongsTo(Audit::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}