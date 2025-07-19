<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class InvoiceItem extends Model
{
    use HasFactory, WebhookEnabled;

    protected $fillable = [
        'invoice_id',
        'fee_category_id',
        'description',
        'amount',
        'quantity',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Get the invoice that owns the item
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the fee category for this item
     */
    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }
}