<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class Asset extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = [
        'name', 'asset_code', 'asset_category_id', 'location',
        'quantity', 'condition', 'purchase_date', 'purchase_price'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }
}